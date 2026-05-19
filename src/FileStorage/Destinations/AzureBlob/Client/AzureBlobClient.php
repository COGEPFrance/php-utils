<?php

namespace Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AzureBlobClient
{
    public function __construct(
        private readonly AzureBlobConfig $config,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function putBlob(string $blobPath, string $content): void
    {
        $blobName = basename($blobPath);
        $url = $this->config->getBlobUrl($blobName);

        $this->logger->info("Uploading blob: {$blobName}");

        $headers = [
            'x-ms-blob-type' => 'BlockBlob',
            'Content-Length' => strlen($content),
        ];

        $this->client->request('PUT', $url, [
           'headers' => $headers,
            'body' => $content,
        ]);
    }

    public function getBlob(string $blobName): string
    {
        $url = $this->config->getBlobUrl($blobName);
        $response = $this->client->request('GET', $url);

        $this->logger->info("Downloading blob: {$blobName}");

        return $response->getContent();
    }
}
