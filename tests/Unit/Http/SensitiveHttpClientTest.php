<?php

namespace Cogep\PhpUtils\Tests\Unit\Http;

use Cogep\PhpUtils\Http\SensitiveHttpClient;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use Mockery;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SensitiveHttpClientTest extends BaseMockeryTestCase
{
    public function testAttachesLoggerWhenInnerClientIsLoggerAware(): void
    {
        $inner = Mockery::mock(HttpClientInterface::class, LoggerAwareInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);

        $inner->shouldReceive('setLogger')
            ->once()
            ->with($logger);

        new SensitiveHttpClient($logger, $inner);
    }

    public function testDoesNotFailWhenInnerClientIsNotLoggerAware(): void
    {
        $inner = Mockery::mock(HttpClientInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);

        $client = new SensitiveHttpClient($logger, $inner);

        $this->assertInstanceOf(SensitiveHttpClient::class, $client);
    }

    public function testDelegatesRequestToInnerClient(): void
    {
        $inner = Mockery::mock(HttpClientInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $inner->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.test', [])
            ->andReturn($response);

        $client = new SensitiveHttpClient($logger, $inner);

        $this->assertSame($response, $client->request('GET', 'https://example.test'));
    }
}
