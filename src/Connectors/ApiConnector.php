<?php

namespace Cogep\PhpUtils\Connectors;

use Cogep\PhpUtils\Connectors\Configs\ApiClientConnectorConfig;
use Cogep\PhpUtils\Connectors\Configs\CacheConnectorConfig;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class ApiConnector
{
    protected ?int $lastTokenTtl = null;

    public function __construct(
        protected readonly HttpClientInterface $client,
        protected readonly ApiClientConnectorConfig $config,
        protected readonly CacheConnectorConfig $cacheConfig,
        protected readonly CacheInterface $cache,
        protected readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return $this
     */
    public function authenticate(): self
    {
        try {
            $this->config->accessToken = $this->cache->get(
                $this->cacheConfig->cacheName,
                function (ItemInterface $item) {
                    $this->logger->info('fetching access token');

                    $token = $this->getToken();
                    $item->expiresAfter($this->lastTokenTtl ?? $this->cacheConfig->cacheStoreTime);

                    return $token;
                }
            );
        } catch (\Exception $e) {
            throw new \RuntimeException("Erreur d'authentification API: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * @return array<string,string|null>
     */
    public function getHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->config->accessToken}",
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @return array<string,string|null>
     */
    public function getAuthHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * @return array<string,string|null>
     */
    public function getAuthParams(): array
    {
        return [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config->clientId,
            'client_secret' => $this->config->clientSecret,
        ];
    }

    /**
     * @return array<string,string|null>
     */
    public function getAuthQueryParams(): array
    {
        return [];
    }

    public function getTokenAttribute(): string
    {
        return 'access_token';
    }

    protected function getToken(): ?string
    {
        $url = $this->config->authUrl ?: "{$this->config->baseUrl}/oauth/token";

        $headers = $this->getAuthHeaders();
        $params = $this->getAuthParams();

        $options = [
            'headers' => $headers,
            'query' => $this->getAuthQueryParams(),
        ];

        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $options['body'] = $params;
        } else {
            $options['json'] = $params;
        }

        $response = $this->client->request('POST', $url, $options);
        $data = $response->toArray();
        $tokenAttribute = $this->getTokenAttribute();
        unset($response);
        if (empty($data[$tokenAttribute])) {
            throw new \RuntimeException('Token invalide ou manquant dans la réponse API.');
        }

        $this->lastTokenTtl = $this->resolveTokenTtl($data);

        return (string) $data[$tokenAttribute];
    }

    /**
     * @param array<string,mixed> $data
     */
    protected function resolveTokenTtl(array $data): ?int
    {
        $expiresIn = $data['expires_in'] ?? null;
        if (! is_numeric($expiresIn)) {
            return null;
        }

        return max(60, (int) $expiresIn - 60);
    }
}
