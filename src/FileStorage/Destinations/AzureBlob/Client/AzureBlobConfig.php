<?php

namespace Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client;

class AzureBlobConfig
{
    public function __construct(
        public ?string $containerName,
        public ?string $accountUrl,
        public ?string $sasUrl
    ) {
        if (empty($this->sasUrl) and (empty($this->accountUrl) || empty($this->containerName))) {
            throw new \InvalidArgumentException('Either sasUrl or accountUrl and containerName must be provided.');
        }
    }

    public function getBlobUrl(string $blobName): string
    {
        if (! empty($this->sasUrl)) {
            return $this->rearrangeSasUrl($blobName);
        }

        return $this->trimSlashes($this->accountUrl) . '/'
            . $this->trimSlashes($this->containerName) . '/'
            . $this->trimSlashes($blobName);
    }

    private function trimSlashes(string $str): string
    {
        return trim($str, '/');
    }

    private function rearrangeSasUrl(string $blobName): string
    {
        $parts = explode('?', $this->sasUrl, 2);
        $base = rtrim($parts[0], '/');
        $query = isset($parts[1]) ? '?' . $parts[1] : '';
        return $base . '/' . ltrim($blobName, '/') . $query;
    }
}
