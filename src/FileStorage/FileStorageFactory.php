<?php

namespace Cogep\PhpUtils\FileStorage;

use Cogep\PhpUtils\FileStorage\Destinations\Local\LocalStorageDestinationPort;
use Cogep\PhpUtils\FileStorage\Enums\FileFormatEnum;
use Cogep\PhpUtils\FileStorage\Enums\FileStorageDestinationEnum;
use Cogep\PhpUtils\FileStorage\Ports\FileDestinationPort;
use Cogep\PhpUtils\FileStorage\Ports\FileFormatterPort;
use Cogep\PhpUtils\FileStorage\Ports\FileFormatterWithWarmupLimitInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class FileStorageFactory
{
    /**
     * @var array<FileStorageDestinationEnum, FileDestinationPort>
     */
    private array $fileDestinations;

    /**
     * @var array<FileFormatEnum, FileFormatterPort>
     */
    private array $fileFormatters;

    private LocalStorageDestinationPort $localStorage;

    public function __construct(
        #[AutowireIterator(FileStorageConsts::FILE_DESTINATION)]
        iterable $fileDestinations,
        #[AutowireIterator(FileStorageConsts::FILE_FORMATTER)]
        iterable $fileFormatters,
        private readonly LoggerInterface $logger,
    ) {
        foreach ($fileDestinations as $fileDestination) {
            if (! $fileDestination instanceof FileDestinationPort) {
                throw new \LogicException('Toutes les destinations doivent implémenter FileDestinationPort');
            }
            $this->fileDestinations[$fileDestination->getDestination()->value] = $fileDestination;
            if ($fileDestination->getDestination() === FileStorageDestinationEnum::LOCAL) {
                if (! $fileDestination instanceof LocalStorageDestinationPort) {
                    throw new \LogicException('Le storage local doit implémenter LocalStorageDestinationPort');
                }
                $this->localStorage = $fileDestination;
            }
        }
        foreach ($fileFormatters as $fileFormatter) {
            if (! $fileFormatter instanceof FileFormatterPort) {
                throw new \LogicException('Toutes les destinations doivent implémenter FileDestinationPort');
            }
            $this->fileFormatters[$fileFormatter->getFileFormat()->value] = $fileFormatter;
        }
        if (! isset($this->localStorage)) {
            throw new \LogicException('Aucun storage local trouvé');
        }
    }

    public function write(string $path, array $data, ?int $warmupLimit = null): PersisterResultEntity
    {
        $count = count($data);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fileFormat = FileFormatEnum::tryFrom(strtolower($extension))->value;

        if ($fileFormat === null || ! isset($this->fileFormatters[$fileFormat])) {
            throw new \InvalidArgumentException(sprintf('Format de fichier non supporté : %s', $extension));
        }

        $this->logger->info(sprintf('Formatter %s', $fileFormat));

        $formatter = $this->fileFormatters[$fileFormat];

        $destination = $this->resolveDestination($path)
            ->value;

        $this->logger->info(sprintf('Destination %s', $destination));

        if (! isset($this->fileDestinations[$destination])) {
            throw new \InvalidArgumentException(sprintf('Destination non supportée : %s', $destination->name));
        }

        if ($warmupLimit !== null && $formatter instanceof FileFormatterWithWarmupLimitInterface) {
            $rawGenerator = $formatter->arrayToRaw($data, $warmupLimit);
        } else {
            $rawGenerator = $formatter->arrayToRaw($data);
        }

        if ($destination === FileStorageDestinationEnum::LOCAL->value) {
            $this->localStorage->saveRawFileFromGenerator($path, $rawGenerator);
            return new PersisterResultEntity($path, $count);
        }

        $fileDestination = $this->fileDestinations[$destination];

        $tempPath = tempnam(sys_get_temp_dir(), 'file_storage_');
        if ($tempPath === false) {
            throw new \RuntimeException('Impossible de créer un fichier temporaire.');
        }

        $this->localStorage->saveRawFileFromGenerator($tempPath, $rawGenerator);
        $completeStream = $this->localStorage->fetchRawFile($tempPath);

        $fileDestination->saveRawFile($this->stripPrefix($path), $completeStream);
        unlink($tempPath);

        return new PersisterResultEntity($path, $count);
    }

    private function resolveDestination(string $path): FileStorageDestinationEnum
    {
        foreach (FileStorageConsts::DESTINATION_MAP as $prefix => $enum) {
            if (str_starts_with($path, $prefix)) {
                return $enum;
            }
        }
        return FileStorageDestinationEnum::LOCAL;
    }

    private function stripPrefix(string $path): string
    {
        foreach (FileStorageConsts::DESTINATION_MAP as $prefix => $enum) {
            if (str_starts_with($path, $prefix)) {
                return substr($path, strlen($prefix));
            }
        }
        return $path;
    }
}
