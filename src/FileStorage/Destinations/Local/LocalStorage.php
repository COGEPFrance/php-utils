<?php

namespace Cogep\PhpUtils\FileStorage\Destinations\Local;

use Cogep\PhpUtils\FileStorage\Enums\FileStorageDestinationEnum;
use RuntimeException;

class LocalStorage implements LocalStorageDestinationPort
{
    public function getDestination(): FileStorageDestinationEnum
    {
        return FileStorageDestinationEnum::LOCAL;
    }

    public function saveRawFile(string $path, string $rawFile): void
    {
        $directory = dirname($path);
        $this->checkDirectory($directory);
        if (file_put_contents($path, $rawFile) === false) {
            throw new RuntimeException(sprintf('Impossible de sauvegarder le fichier : %s', $path));
        }
    }

    public function fetchRawFile(string $path): string
    {
        if (! file_exists($path)) {
            throw new \RuntimeException(sprintf('Le fichier n\'existe pas : %s', $path));
        }

        $rawFile = file_get_contents($path);
        if ($rawFile === false) {
            throw new \RuntimeException(sprintf('Impossible de lire le fichier : %s', $path));
        }

        return $rawFile;
    }

    public function saveRawFileFromGenerator(string $path, iterable $rawGenerator): void
    {
        $directory = dirname($path);
        $this->checkDirectory($directory);
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Impossible d\'ouvrir le fichier : %s', $path));
        }
        foreach ($rawGenerator as $line) {
            fwrite($handle, $line);
            fflush($handle);
        }
        fclose($handle);
    }

    private function checkDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException("Impossible de créer le répertoire : {$directory}");
        }
    }
}
