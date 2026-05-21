<?php

namespace Cogep\PhpUtils\FileStorage\Ports;

use Cogep\PhpUtils\FileStorage\Enums\FileFormatEnum;
use Cogep\PhpUtils\FileStorage\FileStorageConsts;
use Cogep\PhpUtils\FileStorage\Formats\FormatterResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(FileStorageConsts::FILE_FORMATTER)]
interface FileFormatterPort
{
    public function getFileFormat(): FileFormatEnum;

    /**
     * @param iterable<array<string,mixed>> $data
     */
    public function arrayToRaw(iterable $data): FormatterResult;

    /**
     * @return iterable<array<string,mixed>>
     */
    public function rawToArray(string $raw): iterable;
}
