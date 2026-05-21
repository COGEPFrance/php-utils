<?php

namespace Cogep\PhpUtils\FileStorage\Ports;

use Cogep\PhpUtils\FileStorage\Enums\FileStorageDestinationEnum;
use Cogep\PhpUtils\FileStorage\FileStorageConsts;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(FileStorageConsts::FILE_DESTINATION)]
interface FileDestinationPort
{
    public function getDestination(): FileStorageDestinationEnum;

    public function saveRawFile(string $path, string $rawFile): void;

    public function fetchRawFile(string $path): string;
}
