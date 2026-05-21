<?php

namespace Cogep\PhpUtils\FileStorage\Formats\Json;

use Cogep\PhpUtils\Classes\EntityInterface;
use Cogep\PhpUtils\FileStorage\Enums\FileFormatEnum;
use Cogep\PhpUtils\FileStorage\Formats\FormatterResult;
use Cogep\PhpUtils\FileStorage\Ports\FileFormatterPort;
use Generator;

/**
 * @template T of EntityInterface
 */
class JsonFormatter implements FileFormatterPort
{
    public function getFileFormat(): FileFormatEnum
    {
        return FileFormatEnum::JSON;
    }

    /**
     * @param iterable<array<string,mixed>> $data
     */
    public function arrayToRaw(iterable $data): FormatterResult
    {
        $lines = [];
        $count = 0;
        $first = true;
        foreach ($data as $entry) {
            if (! $first) {
                $lines[] = ',';
            }
            $json = json_encode($entry, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new \RuntimeException('Erreur d\'encodage JSON');
            }
            $lines[] = $json;
            $first = false;
            $count++;
        }
        array_unshift($lines, '[');
        $lines[] = ']';
        $generator = (function () use ($lines) {
            foreach ($lines as $line) {
                yield $line;
            }
        })();

        return new FormatterResult($generator, $count);
    }

    /**
     * @return Generator<array<string,mixed>>
     */
    public function rawToArray(string $raw): Generator
    {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            return;
        }
        foreach ($data as $item) {
            yield $item;
        }
    }
}
