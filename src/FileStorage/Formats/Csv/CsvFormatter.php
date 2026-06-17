<?php

namespace Cogep\PhpUtils\FileStorage\Formats\Csv;

use Cogep\PhpUtils\FileStorage\Enums\FileFormatEnum;
use Cogep\PhpUtils\FileStorage\Formats\CounterRef;
use Cogep\PhpUtils\FileStorage\Formats\FormatterResult;
use Cogep\PhpUtils\FileStorage\Ports\FileFormatterWithWarmupLimitInterface;

class CsvFormatter implements FileFormatterWithWarmupLimitInterface
{
    public const DELIMITER = ',';

    public function __construct()
    {
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

        $headers = fgetcsv($stream, 0, self::DELIMITER, '"', '');

        if (! is_array($headers) || empty(array_filter($headers))) {
            fclose($stream);
            throw new \RuntimeException('le contenu du CSV est vide ou mal formé.');
        }

        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        $headerCount = count($headers);

        try {
            while (($row = fgetcsv($stream, 0, self::DELIMITER, '"', '')) !== false) {
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

    /**
     * Truly lazy/streaming implementation.
     *
     * Buffers only the first $warmupLimit entries to discover CSV headers,
     * emits the header row and buffered rows, then streams each subsequent
     * entry immediately — without waiting for the entire dataset to be consumed.
     */
    public function arrayToRaw(iterable $data, int $warmupLimit = 100): FormatterResult
    {
        $counter = new CounterRef();

        return new FormatterResult($this->buildGenerator($data, $warmupLimit, $counter), $counter);
    }

    /**
     * @param iterable<array<string,mixed>> $data
     */
    private function buildGenerator(iterable $data, int $warmupLimit, CounterRef $counter): \Generator
    {
        /** @var array<array<string,mixed>> $buffer */
        $buffer = [];
        /** @var array<string> $headers */
        $headers = [];
        $headerWritten = false;

        foreach ($data as $entry) {
            $dataArray = $this->toArray($entry);
            $counter->value++;

            if (! $headerWritten) {
                $this->updateHeaders($dataArray, $headers);
                $buffer[] = $dataArray;

                if (count($buffer) >= $warmupLimit) {
                    yield from $this->yieldWarmupBuffer($buffer, $headers);
                    $buffer = [];
                    $headerWritten = true;
                }
            } else {
                yield $this->formatCsvRow($dataArray, $headers);
            }
        }

        if (! $headerWritten && count($buffer) > 0) {
            yield from $this->yieldWarmupBuffer($buffer, $headers);
        }
    }

    /**
     * Yields the header row followed by all buffered rows.
     *
     * @param array<array<string,mixed>> $buffer
     * @param array<string> $headers
     */
    private function yieldWarmupBuffer(array $buffer, array $headers): \Generator
    {
        yield $this->formatCsvRow(array_combine($headers, $headers), $headers);
        foreach ($buffer as $bufferedRow) {
            yield $this->formatCsvRow($bufferedRow, $headers);
        }
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
            $line[] = $value;
        }
        return implode(self::DELIMITER, $line) . "\n";
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
        return trim(str_replace([self::DELIMITER, "\r", "\n"], [' ', '', ' '], $value));
    }
}
