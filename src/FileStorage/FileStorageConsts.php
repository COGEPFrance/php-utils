<?php

namespace Cogep\PhpUtils\FileStorage;

use Cogep\PhpUtils\FileStorage\Enums\FileStorageDestinationEnum;

class FileStorageConsts
{
    public const FILE_FORMATTER = 'file_formatter';

    public const FILE_DESTINATION = 'file_destination';

    public const DESTINATION_MAP = [
        'azure://' => FileStorageDestinationEnum::AZURE,
    ];
}
