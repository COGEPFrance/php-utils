<?php

namespace Cogep\PhpUtils\FileStorage\Enums;

enum FileStorageDestinationEnum: string
{
    case AZURE = 'azure';
    case LOCAL = 'local';
}
