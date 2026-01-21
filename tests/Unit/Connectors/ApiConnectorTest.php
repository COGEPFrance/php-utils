<?php


namespace Unit\Connectors;

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

class ApiConnectorTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;

    private CacheInterface&MockObject $cache;

    private LoggerInterface&MockObject $logger;

    private ApiClientConnectorConfig $apiConfig;

    private CacheConnectorConfig $cacheConfig;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->apiConfig = new ApiClientConnectorConfig('https://api.example.com', 'id', 'secret');

        $this->cacheConfig = new CacheConnectorConfig('test_cache', 3600);
    }

    public function testAuthenticateUsesCacheWhenPresent(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('test_cache')
            ->willReturn('CACHED_TOKEN');

        $connector = $this->createConnector();
        $connector->authenticate();

        $this->assertSame('CACHED_TOKEN', $this->apiConfig->accessToken);
    }

    public function testAuthenticateFetchesTokenWhenCacheEmpty(): void
    {
        $this->cache->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')
            ->willReturn([
                'access_token' => 'NEW_TOKEN',
            ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $this->anything())
            ->willReturn($response);

        $connector = $this->createConnector();
        $connector->authenticate();

        $this->assertSame('NEW_TOKEN', $this->apiConfig->accessToken);
    }

    public function testAuthenticateThrowsExceptionOnInvalidResponse(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Erreur d'authentification API");

        $this->cache->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')
            ->willReturn([
                'error' => 'invalid_client',
            ]);

        $this->httpClient->method('request')
            ->willReturn($response);

        $connector = $this->createConnector();
        $connector->authenticate();
    }

    public function testGetHeadersContainsBearerToken(): void
    {
        $this->apiConfig->accessToken = 'MY_TOKEN';
        $connector = $this->createConnector();

        $headers = $connector->getHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals('Bearer MY_TOKEN', $headers['Authorization']);
    }

    private function createConnector(): ApiConnector
    {
        return new class(
            $this->httpClient,
            $this->apiConfig,
            $this->cacheConfig,
            $this->cache,
            $this->logger
        ) extends ApiConnector {
        };
    }
}
