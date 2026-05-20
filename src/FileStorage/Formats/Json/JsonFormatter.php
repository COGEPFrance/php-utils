<?php

namespace Cogep\PhpUtils\FileStorage\Formats\Json;

use Cogep\PhpUtils\Classes\EntityInterface;
use Cogep\PhpUtils\FileStorage\Enums\FileFormatEnum;
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

    public function arrayToRaw(iterable $data): Generator
    {
        yield '[';
        $first = true;
        foreach ($data as $entry) {
            if (! $first) {
                yield ',';
            }
            yield json_encode($entry, JSON_UNESCAPED_UNICODE);
            $first = false;
        }
        yield ']';
    }

    public function rawToArray(string $raw): Generator
    {
        $lines = explode(PHP_EOL, $raw);

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            yield json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        }
    }
}
