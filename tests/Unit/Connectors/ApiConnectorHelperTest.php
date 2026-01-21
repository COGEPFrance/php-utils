<?php

namespace Unit\Connectors;

use Cogep\PhpUtils\Connectors\ApiConnectorHelper;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\JsonStreamer\StreamReaderInterface;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiConnectorHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ApiConnectorHelper $helper;

    private $logger;

    protected function setUp(): void
    {
        $this->logger = \Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $streamReader = \Mockery::mock(StreamReaderInterface::class);
        $this->helper = new ApiConnectorHelper($streamReader, $this->logger, new MockClock());
    }

    public function testProcessInBatchesHandlesRetrySuccess(): void
    {
        $items = [1];
        $responses = [
            new MockResponse('Error', [
                'http_code' => 500,
            ]),
            new MockResponse(json_encode([
                'id' => 'retry_ok',
            ]), [
                'http_code' => 200,
            ]),
        ];
        $client = new MockHttpClient($responses);

        $this->logger->shouldReceive('warning')
            ->with(\Mockery::pattern('/Ajouté au rattrapage/'))
            ->atLeast()
            ->once()
            ->ordered();

        $this->logger->shouldReceive('warning')
            ->with(\Mockery::pattern('/Lancement rattrapage/'))
            ->atLeast()
            ->once()
            ->ordered();

        $generator = $this->helper->processInBatches(
            $client,
            'TEST_API',
            $items,
            1,
            fn ($c) => $c->request('GET', '/'),
            fn ($r) => yield json_decode($r->getContent(), true)
        );

        $results = iterator_to_array($generator);
        $this->assertEquals('retry_ok', $results[0]['id']);
    }

    public function testProcessInBatchesHandlesRequestCallbackException(): void
    {
        $items = ['item_1'];
        $client = new MockHttpClient();

        $requestCallback = function () {
            throw new \Exception('Parsing error');
        };

        $this->logger->shouldReceive('error')
            ->with(\Mockery::pattern('/Échec de préparation de requête/'))
            ->atLeast()
            ->once()
            ->ordered();

        $this->logger->shouldReceive('error')
            ->with(\Mockery::pattern('/ABANDON DÉFINITIF/'))
            ->once()
            ->ordered();

        $generator = $this->helper->processInBatches($client, 'TEST_API', $items, 1, $requestCallback, function () {
            yield [];
        });

        iterator_to_array($generator);
    }

    public function testProcessInBatchesHandlesGlobalStreamCrash(): void
    {
        $items = ['item_1'];
        $client = \Mockery::mock(HttpClientInterface::class);
        $client->shouldReceive('stream')
            ->once()
            ->andThrow(new \Exception('Stream broken'));
        $client->shouldReceive('stream')
            ->andReturn(new \ArrayIterator([]));

        $this->logger->shouldReceive('warning')
            ->with(\Mockery::pattern('/Crash itérateur: Stream broken/'))
            ->atLeast()
            ->once()
            ->ordered();

        $this->logger->shouldReceive('warning')
            ->with(\Mockery::pattern('/Lancement rattrapage/'))
            ->atLeast()
            ->once()
            ->ordered();

        $generator = $this->helper->processInBatches(
            $client,
            'TEST_API',
            $items,
            1,
            fn () => $this->createMock(ResponseInterface::class),
            function () {
                yield [];
            }
        );

        iterator_to_array($generator);
    }

    public function testProcessInBatchesCallsCleanupAndLogsMemory(): void
    {
        $items = [1, 2];
        $client = new MockHttpClient([new MockResponse('{}'), new MockResponse('{}')]);

        $this->logger->shouldReceive('info')
            ->with(\Mockery::pattern('/Mémoire après nettoyage/'))
            ->times(2);

        $generator = $this->helper->processInBatches(
            $client,
            'API',
            $items,
            1,
            fn ($c) => $c->request('GET', '/'),
            function () {
                yield 'ok';
            }
        );

        iterator_to_array($generator);
    }

    public function testProcessInBatchesGivesUpAfterMaxRetries(): void
    {
        $items = ['persistent_fail'];

        $client = new MockHttpClient([
            new MockResponse('Fail 1', [
                'http_code' => 500,
            ]),
            new MockResponse('Fail 2', [
                'http_code' => 500,
            ]),
            new MockResponse('Fail 3', [
                'http_code' => 500,
            ]),
        ]);

        $this->logger->shouldReceive('error')
            ->with(\Mockery::pattern('/ABANDON DÉFINITIF/'))
            ->once();

        $generator = $this->helper->processInBatches(
            $client,
            'TEST',
            $items,
            1,
            fn ($c) => $c->request('GET', '/'),
            fn () => yield []
        );

        iterator_to_array($generator);
    }

    public function testProcessInBatchesHandlesChunkTimeout(): void
    {
        $items = ['timeout_item'];

        $response = \Mockery::mock(ResponseInterface::class);
        $client = \Mockery::mock(HttpClientInterface::class);
        $chunk = \Mockery::mock(ChunkInterface::class);

        $response->shouldReceive('getInfo')
            ->with('url')
            ->andReturn('http://api.test/timeout');
        $response->shouldReceive('cancel')
            ->byDefault();

        $chunk->shouldReceive('isTimeout')
            ->andReturn(true);
        $chunk->shouldReceive('isLast')
            ->andReturn(false);

        $client->shouldReceive('stream')
            ->andReturn([[$response, $chunk]]);
        $client->shouldReceive('stream')
            ->andReturn([]);

        $this->logger->shouldReceive('warning')
            ->with(\Mockery::pattern('/Timeout détecté|Échec sur/'))
            ->atLeast()
            ->once();

        $this->logger->shouldReceive('warning')
            ->with(\Mockery::pattern('/Lancement rattrapage/'))
            ->atLeast()
            ->once();

        $generator = $this->helper->processInBatches($client, 'TEST', $items, 1, fn () => $response, fn () => yield []);
        iterator_to_array($generator);
    }
}
