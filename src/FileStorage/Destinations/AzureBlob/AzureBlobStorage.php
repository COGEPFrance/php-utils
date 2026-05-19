<?php

namespace Cogep\PhpUtils\FileStorage\Destinations\AzureBlob;

use Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client\AzureBlobClient;
use Cogep\PhpUtils\FileStorage\Enums\FileStorageDestinationEnum;
use Cogep\PhpUtils\FileStorage\Ports\FileDestinationPort;

class AzureBlobStorage implements FileDestinationPort
{
    public function __construct(
        private readonly AzureBlobClient $client
    ) {
    }

    public function getDestination(): FileStorageDestinationEnum
    {
        return FileStorageDestinationEnum::AZURE;
    }

    public function fetchRawFile(string $path): string
    {
        return $this->client->getBlob($path);
    }

    public function saveRawFile(string $path, string $rawFile): void
    {
        $this->client->putBlob($path, $rawFile);
    }
}
