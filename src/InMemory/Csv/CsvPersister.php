<?php

namespace Cogep\PhpUtils\InMemory\Csv;

use Cogep\PhpUtils\InMemory\NoDatasToSaveException;
use Cogep\PhpUtils\InMemory\PersisterResultEntity;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CsvPersister
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param iterable<int|string, mixed> $datas
     */
    public function save(string $path, iterable $datas, int $warmupLimit = 100): PersisterResultEntity
    {
        $handler = $this->openFile($path);

        $count = 0;
        /** @var array<string> $headers */
        $headers = [];
        /** @var array<int, array<string, mixed>> $buffer */
        $buffer = [];
        $headerWritten = false;

        foreach ($datas as $data) {
            /** @var array<int|string, mixed> $dataArray */
            $dataArray = is_object($data) ? get_object_vars($data) : (array) $data;

            if ($headerWritten) {
                $this->writeRow($handler, $dataArray, $headers);
                ++$count;

                continue;
            }

            $this->logger->info(
                sprintf(
                    'En attente de %s elements supplémentaires pour écrire l\'en tête du fichier',
                    $warmupLimit - $count
                )
            );
            $buffer[] = $dataArray;

            $this->updateHeaders($dataArray, $headers);

            ++$count;

            if ($count === $warmupLimit) {
                $this->logger->info('Ecriture des en-têtes dans le CSV', $headers);
                $this->writeHeadersAndBuffer($handler, $headers, $buffer);

                $headerWritten = true;
                $buffer = [];
            }
        }

        if ($count === 0) {
            throw new NoDatasToSaveException();
        }

        if (! $headerWritten) {
            $remaining = $warmupLimit - $count;
            $this->logger->info(sprintf(
                "Le nombre d'élément total (%s) est inférieur au nombre d'éléments voulus pour récupérer les en-têtes %s",
                count($buffer),
                $remaining
            ));

            $this->writeHeadersAndBuffer($handler, $headers, $buffer);
        }

        fclose($handler);

        $this->logger->info('Fichier CSV généré en streaming direct', [
            'filepath' => $path,
            'count' => $count,
            'columns' => $headers,
        ]);

        return new PersisterResultEntity($path, $count);
    }

    /**
     * @param resource $handler
     * @param array<string> $headers
     * @param array<int, array<string|int, mixed>> $buffer
     */
    public function writeHeadersAndBuffer($handler, array $headers, array $buffer): void
    {
        fputcsv($handler, $headers, separator: ';', escape: '');

        foreach ($buffer as $buffered) {
            $this->writeRow($handler, $buffered, $headers);
        }
    }

    private function formatValue(mixed $value): string
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_bool($value) => (string) (int) $value,
            is_array($value) => $this->formatArray($value),
            default => $this->sanitizeString((string) $value),
        };
    }

    /**
     * @param array<mixed,mixed> $value
     */
    private function formatArray(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function sanitizeString(string $value): string
    {
        return trim(str_replace([';', "\r", "\n"], [' ', '', ' '], $value));
    }

    /**
     * @param resource $handle
     * @param array<string|int, mixed> $data
     * @param array<string> $headers
     */
    private function writeRow($handle, array $data, array $headers): void
    {
        $line = [];
        foreach ($headers as $header) {
            if (isset($data[$header])) {
                $line[] = $this->formatValue($data[$header]);
            } else {
                $line[] = '';
            }
        }
        fputcsv($handle, $line, separator: ';', escape: '');
    }

    /**
     * @param array<int|string,mixed> $data
     * @param array<string> $headers
     */
    private function updateHeaders(array $data, array &$headers): void
    {
        $keys = array_map(fn ($k) => (string) $k, array_keys($data));
        $headers = array_values(array_unique([...$headers, ...$keys]));
    }

    /**
     * @return resource
     */
    private function openFile(string $filepath)
    {
        $directory = dirname($filepath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf("Le répertoire %s n'a pas pu être créé", $directory));
        }

        if (! $handler = fopen($filepath, 'w')) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier : {$filepath}");
        }

        $this->logger->info("Ouverture du fichier d'export CSV", [$filepath]);

        return $handler;
    }
}
