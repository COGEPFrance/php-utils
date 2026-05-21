<?php

namespace Cogep\PhpUtils\FileStorage\Formats\Csv;

use Cogep\PhpUtils\FileStorage\Enums\FileFormatEnum;
use Cogep\PhpUtils\FileStorage\Formats\FormatterResult;
use Cogep\PhpUtils\FileStorage\Ports\FileFormatterWithWarmupLimitInterface;
use Psr\Log\LoggerInterface;

class CsvFormatter implements FileFormatterWithWarmupLimitInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function getFileFormat(): FileFormatEnum
    {
        return FileFormatEnum::CSV;
    }

    /**
     * @return iterable<array<string,mixed>>
     */
    public function rawToArray(string $raw): iterable
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Impossible d’ouvrir un flux temporaire.');
        }
        fwrite($stream, $raw);
        rewind($stream);

        $headers = fgetcsv($stream, 0, ';', '"', '');

        if (! is_array($headers) || empty(array_filter($headers))) {
            fclose($stream);
            throw new \RuntimeException('le contenu du CSV est vide ou mal formé.');
        }

        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        $headerCount = count($headers);

        try {
            while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) {
                if (count(array_filter($row)) === 0) {
                    continue;
                }

                $row = $this->processRow($row, $headerCount);
                yield array_combine($headers, $row);
            }
        } finally {
            fclose($stream);
        }
    }

    public function arrayToRaw(iterable $data, int $warmupLimit = 100): FormatterResult
    {
        [$buffer, $headers] = $this->bufferDataAndHeaders($data, $warmupLimit);
        $lines = $this->generateLines($buffer, $headers);
        $count = count($buffer);

        $generator = (function () use ($lines) {
            foreach ($lines as $line) {
                yield $line;
            }
        })();

        return new FormatterResult($generator, $count > 0 ? $count : 0);
    }

    /**
     * @param iterable<array<string, mixed>> $data
     * @return array{0: array<array<string, mixed>>, 1: array<string>}
     */
    private function bufferDataAndHeaders(iterable $data, int $warmupLimit): array
    {
        $buffer = [];
        $headers = [];
        $headerWritten = false;
        foreach ($data as $entry) {
            $dataArray = $this->toArray($entry);
            if (! $headerWritten) {
                $buffer[] = $dataArray;
                $this->updateHeaders($dataArray, $headers);
                if (count($buffer) >= $warmupLimit) {
                    $headerWritten = true;
                }
            } else {
                $buffer[] = $dataArray;
            }
        }
        return [$buffer, $headers];
    }

    /**
     * @param array<array<string, mixed>> $buffer
     * @param array<string> $headers
     * @return array<string>
     */
    private function generateLines(array $buffer, array $headers): array
    {
        $lines = [];
        if (count($buffer) > 0) {
            $lines[] = $this->formatCsvRow(array_combine($headers, $headers), $headers);
            foreach ($buffer as $dataArray) {
                $lines[] = $this->formatCsvRow($dataArray, $headers);
            }
        }
        return $lines;
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(mixed $entry): array
    {
        return is_object($entry) ? get_object_vars($entry) : (array) $entry;
    }

    /**
     * @param array<int,mixed> $row
     * @return array<int,mixed>
     */
    private function processRow(array $row, int $headerCount): array
    {
        $rowCount = count($row);

        if ($rowCount !== $headerCount) {
            $row = ($rowCount < $headerCount)
                ? array_pad($row, $headerCount, '')
                : array_slice($row, 0, $headerCount);
        }

        return $row;
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
     * @param array<int|string,mixed> $data
     * @param array<string> $headers
     */
    private function updateHeaders(array $data, array &$headers): void
    {
        $keys = array_map(fn ($k) => (string) $k, array_keys($data));
        $headers = array_values(array_unique([...$headers, ...$keys]));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string> $headers
     */
    private function formatCsvRow(array $data, array $headers = []): string
    {
        $line = [];
        foreach ($headers as $header) {
            $value = isset($data[$header]) ? $this->formatValue($data[$header]) : '';
            $this->logger->debug('header', [
                'header' => $header,
                'value' => $value,
                'data' => $data,
            ]);
            $line[] = $value;
        }
        return implode(';', $line) . "\n";
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
}
