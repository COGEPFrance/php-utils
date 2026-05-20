<?php

namespace Cogep\PhpUtils\Tests\Unit\FileStorage\Destinations\AzureBlob\Client;

use Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client\AzureBlobClient;
use Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client\AzureBlobConfig;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use Mockery;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AzureBlobClientTest extends BaseMockeryTestCase
{
    private AzureBlobClient $client;

    protected function setUp(): void
    {
        $config = new AzureBlobConfig('https://account.blob.core.windows.net', 'sasToken=abc');
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);

        $this->client = new AzureBlobClient($config, $httpClient, $logger);
    }

    public function testGetBlobUrlSansSas()
    {
        $url = $this->invokeGetBlobUrl('container/fichier.txt', false);
        $this->assertSame('https://account.blob.core.windows.net/container/fichier.txt', $url);
    }

    public function testGetBlobUrlAvecSas()
    {
        $url = $this->invokeGetBlobUrl('container/fichier.txt', true);
        $this->assertSame('https://account.blob.core.windows.net/container/fichier.txt?sasToken=abc', $url);
    }

    public function testGetBlobUrlFormatInvalide()
    {
        $this->expectException(RuntimeException::class);
        $this->invokeGetBlobUrl('container-seul', false);
    }

    public function testPutBlobCallsHttpClientWithCorrectHeaders()
    {
        $config = new AzureBlobConfig('https://account.blob.core.windows.net', 'sasToken=abc');
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $client = new AzureBlobClient($config, $httpClient, $logger);

        $logger->shouldReceive('info')
            ->once();
        $httpClient->shouldReceive('request')
            ->with('PUT', Mockery::type('string'), Mockery::on(function ($options) {
                return isset($options['headers']['x-ms-blob-type']) &&
                    $options['headers']['x-ms-blob-type'] === 'BlockBlob';
            }))
            ->andReturn(Mockery::mock(ResponseInterface::class, [
                'getStatusCode' => 200,
            ]));

        $client->putBlob('container/file.txt', 'data');
    }

    public function testGetBlobReturnsContent()
    {
        $config = new AzureBlobConfig('https://account.blob.core.windows.net', 'sasToken=abc');
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $client = new AzureBlobClient($config, $httpClient, $logger);

        $logger->shouldReceive('info')
            ->once();
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')
            ->andReturn(200);
        $mockResponse->shouldReceive('getContent')
            ->andReturn('blob-content');
        $httpClient->shouldReceive('request')
            ->andReturn($mockResponse);

        $result = $client->getBlob('container/file.txt');
        $this->assertSame('blob-content', $result);
    }

    private function invokeGetBlobUrl(string $blobPath, bool $withSas)
    {
        $ref = new ReflectionClass($this->client);
        $method = $ref->getMethod('getBlobUrl');
        return $method->invoke($this->client, $blobPath, $withSas);
    }
}
