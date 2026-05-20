<?php

namespace Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
        $this->logger->info("Uploading blob: {$blobPath}");

        $headers = [
            'x-ms-blob-type' => 'BlockBlob',
            'Content-Length' => strlen($content),
        ];

        $this->callAzureStorageApi('PUT', $blobPath, [
            'headers' => $headers,
            'body' => $content,
        ]);
    }

    public function getBlob(string $blobPath): string
    {
        $this->logger->info("Downloading blob: {$blobPath}");

        $response = $this->callAzureStorageApi('GET', $blobPath);

        return $response->getContent();
    }

    private function callAzureStorageApi(
        string $method,
        string $blobPath,
        array $options = []
    ): ResponseInterface {
        foreach ([false, true] as $withSas) {
            $url = $this->getBlobUrl($blobPath, $withSas);
            try {
                $response = $this->client->request($method, $url, $options);
                if ($this->isSuccessful($response)) {
                    return $response;
                }
                $this->handleUnsuccessfulStatus($response, $withSas);
            } catch (\Throwable $e) {
                $this->logAzureError($e, $withSas);
                if ($withSas) {
                    throw new \RuntimeException("Impossible d'accéder au blob Azure, même avec SAS.", 0, $e);
                }
            }
        }
        throw new \RuntimeException("Impossible d'accéder au blob Azure.");
    }

    private function logAzureError(\Throwable $e, bool $withSas): void
    {
        $level = $withSas ? 'error' : 'warning';
        $this->logger->{$level}(
            'Azure call failed' .
            ($withSas ? ' with SAS' : ' without SAS') .
            ': ' . $e->getMessage()
        );
    }

    private function handleUnsuccessfulStatus(ResponseInterface $response, bool $withSas): void
    {
        $status = $response->getStatusCode();
        if (! in_array($status, [401, 403], true)) {
            throw new \RuntimeException('Erreur Azure' . ($withSas ? ' (avec SAS)' : '') . ": {$status}");
        }
    }

    private function isSuccessful(ResponseInterface $response): bool
    {
        $status = $response->getStatusCode();
        return $status >= 200 && $status < 300;
    }

    private function getBlobUrl(string $blobPath, bool $withSasToken = false): string
    {
        $parts = explode('/', $this->trimSlashes($blobPath), 2);
        if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
            throw new \RuntimeException(
                'Le chemin du blob doit être au format "container/nom_fichier.extension". Reçu : ' . $blobPath
            );
        }
        [$container, $blob] = $parts;

        if (empty($container) || empty($blob)) {
            throw new \RuntimeException('Le chemin du blob est invalide.');
        }

        $baseUrl = rtrim($this->config->accountUrl, '/') . '/' . $container . '/' . $blob;

        if (! empty($this->config->sasToken) && $withSasToken) {
            $separator = str_contains($baseUrl, '?') ? '&' : '?';
            return $baseUrl . $separator . ltrim($this->config->sasToken, '?');
        }

        return $baseUrl;
    }

    private function trimSlashes(string $str): string
    {
        return trim($str, '/');
    }
}
