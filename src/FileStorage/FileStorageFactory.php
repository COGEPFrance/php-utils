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
    private LocalStorageDestinationPort $localStorage;

    /**
     * @var array<FileStorageDestinationEnum,FileDestinationPort>
     */
    private array $fileDestinations;

    /**
     * @var array<FileFormatEnum,FileFormatterPort>
     */
    private array $fileFormatters;

    /**
     * @param array<mixed,mixed> $fileDestinations
     * @param array<mixed,mixed> $fileFormatters
     */
    public function __construct(
        #[AutowireIterator(FileStorageConsts::FILE_DESTINATION)]
        iterable $fileDestinations,
        #[AutowireIterator(FileStorageConsts::FILE_FORMATTER)]
        iterable $fileFormatters,
        public readonly LoggerInterface $logger,
    ) {
        $this->initFileDestinations($fileDestinations);
        $this->initFileFormatters($fileFormatters);
        if (! isset($this->localStorage)) {
            throw new \LogicException('Aucun storage local trouvé');
        }
    }

    /**
     * @param array<mixed,mixed> $data
     */
    public function write(string $path, iterable $data, ?int $warmupLimit = null): PersisterResultEntity
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fileFormat = FileFormatEnum::tryFrom(strtolower($extension))?->value;

        if ($fileFormat === null || ! isset($this->fileFormatters[$fileFormat])) {
            throw new \InvalidArgumentException(sprintf('Format de fichier non supporté : %s', $extension));
        }

        $this->logger->info(sprintf('Formatter %s', $fileFormat));

        $formatter = $this->fileFormatters[$fileFormat];

        $destination = $this->resolveDestination($path)
            ->value;

        $this->logger->info(sprintf('Destination %s', $destination));

        if (! isset($this->fileDestinations[$destination])) {
            throw new \InvalidArgumentException(sprintf('Destination non supportée : %s', $destination));
        }

        if ($warmupLimit !== null && $formatter instanceof FileFormatterWithWarmupLimitInterface) {
            $formatterResult = $formatter->arrayToRaw($data, $warmupLimit);
        } else {
            $formatterResult = $formatter->arrayToRaw($data);
        }

        $rawGenerator = $formatterResult->raw;

        if ($destination === FileStorageDestinationEnum::LOCAL->value) {
            $this->localStorage->saveRawFileFromGenerator($path, $rawGenerator);
            return new PersisterResultEntity($path, $formatterResult->count);
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

        return new PersisterResultEntity($path, $formatterResult->count);
    }

    /**
     * @param array<mixed,mixed> $fileDestinations
     */
    private function initFileDestinations(iterable $fileDestinations): void
    {
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
    }

    /**
     * @param array<mixed,mixed> $fileFormatters
     */
    private function initFileFormatters(iterable $fileFormatters): void
    {
        foreach ($fileFormatters as $fileFormatter) {
            if (! $fileFormatter instanceof FileFormatterPort) {
                throw new \LogicException('Toutes les destinations doivent implémenter FileDestinationPort');
            }
            $this->fileFormatters[$fileFormatter->getFileFormat()->value] = $fileFormatter;
        }
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
