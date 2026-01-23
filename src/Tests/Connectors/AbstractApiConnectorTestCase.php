<?php

namespace Cogep\PhpUtils\Tests\Connectors;

use Cogep\PhpUtils\Connectors\ApiConnector;
use Cogep\PhpUtils\Connectors\Configs\ApiClientConnectorConfig;
use Cogep\PhpUtils\Connectors\Configs\CacheConnectorConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractApiConnectorTestCase extends TestCase
{
    protected HttpClientInterface&MockObject $httpClient;

    protected CacheInterface&MockObject $cache;

    protected LoggerInterface&MockObject $logger;

    protected ApiClientConnectorConfig $apiConfig;

    protected CacheConnectorConfig $cacheConfig;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Valeurs par défaut pour les tests
        $this->apiConfig = new ApiClientConnectorConfig(
            'https://api.test.com',
            'id',
            'secret'
        );
        $this->cacheConfig = new CacheConnectorConfig('test_cache', 3600);
    }

    public function testAuthenticateUsesCacheWhenPresent(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->cacheConfig->cacheName)
            ->willReturn('CACHED_TOKEN');

        $this->createConnector()
            ->authenticate();

        $this->assertSame('CACHED_TOKEN', $this->apiConfig->accessToken);
    }

    public function testAuthenticateFetchesTokenWhenCacheEmpty(): void
    {
        $this->cache->method('get')
            ->willReturnCallback(fn ($key, $callback) => $callback($this->createMock(ItemInterface::class)));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')
            ->willReturn([
                'access_token' => 'NEW_TOKEN',
            ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->createConnector()
            ->authenticate();

        $this->assertSame('NEW_TOKEN', $this->apiConfig->accessToken);
    }

    public function testGetHeadersContainsBearerToken(): void
    {
        $this->apiConfig->accessToken = 'MY_TOKEN';
        $headers = $this->createConnector()
            ->getHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals('Bearer MY_TOKEN', $headers['Authorization']);
    }

    public function testAuthenticateThrowsRuntimeExceptionOnFailure(): void
    {
        $this->cache->method('get')
            ->willThrowException(new \Exception('Cache error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Erreur d'authentification API: Cache error");

        $connector = $this->createConnector();
        $connector->authenticate();
    }

    /**
     * Méthode abstraite pour instancier le connecteur spécifique
     */
    abstract protected function createConnector(): ApiConnector;
}
