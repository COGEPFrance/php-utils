<?php

namespace Cogep\PhpUtils\FileStorage\Destinations\Local;

use Cogep\PhpUtils\FileStorage\Ports\FileDestinationPort;

interface LocalStorageDestinationPort extends FileDestinationPort
{
    /**
     * Écriture progressive à partir d’un générateur.
     * @param iterable<string> $rawGenerator
     */
    public function saveRawFileFromGenerator(string $path, iterable $rawGenerator): void;
}
