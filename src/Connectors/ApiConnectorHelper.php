<?php

namespace Cogep\PhpUtils\Connectors;

use Cogep\PhpUtils\Classes\DTOInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\JsonStreamer\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @template T
 * @template R of DTOInterface
 */
class ApiConnectorHelper
{
    /**
     * @param StreamReaderInterface<array<string, mixed>> $streamReader
     */
    public function __construct(
        #[Target('json_streamer.stream_reader')]
        private readonly StreamReaderInterface $streamReader,
        protected readonly LoggerInterface $logger,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Exécute des requêtes en batch avec pool.
     *
     * @param array<T> $items À traiter
     * @param positive-int $poolSize Nombre de requêtes simultanées
     * @param callable(HttpClientInterface, T): ResponseInterface $requestCallback
     * @param callable(ResponseInterface): iterable<R> $responseCallback
     *
     * @return \Generator<R>
     */
    public function processInBatches(
        HttpClientInterface $client,
        string $api,
        array $items,
        int $poolSize,
        callable $requestCallback,
        callable $responseCallback,
        int $retryLevel = 0
    ): \Generator {
        /** @var array<T> $failedItems */
        $failedItems = [];

        /** @var array<int, array<T>> $chunks */
        $chunks = array_chunk($items, $poolSize);
        $totalBatches = count($chunks);

        if ($retryLevel === 0) {
            $this->logger->info("Total batches: {$totalBatches}");
        }

        foreach ($chunks as $batchIndex => $batchItems) {
            $this->logger->info("API {$api} Batch {$batchIndex} démarré");

            $responsesMap = $this->prepareBatchRequests($client, $batchItems, $requestCallback, $failedItems);

            if ($responsesMap->count() === 0) {
                continue;
            }

            yield from $this->streamBatchResponses($client, $responsesMap, $responseCallback, $failedItems);

            $this->cleanup($api, $batchIndex);
            if ($batchIndex < count($chunks) - 1) {
                $this->clock->sleep(1);
            }
        }
        yield from $this->handleRetries($client, $api, $failedItems, $requestCallback, $responseCallback, $retryLevel);
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    public function streamResponse(ResponseInterface $response): iterable
    {
        if (! method_exists($response, 'toStream')) {
            throw new \RuntimeException('La réponse HTTP ne supporte pas le streaming.');
        }

        $phpStream = $response->toStream();
        return $this->streamReader->read($phpStream, Type::iterable());
    }

    /**
     * @param array<T> $batchItems
     * @param callable(HttpClientInterface, T): ResponseInterface $requestCallback
     * @param array<T> $failedItems
     *
     * @return \SplObjectStorage<ResponseInterface, T>
     */
    private function prepareBatchRequests(
        HttpClientInterface $client,
        array $batchItems,
        callable $requestCallback,
        array &$failedItems
    ): \SplObjectStorage {
        /** @var \SplObjectStorage<ResponseInterface, T> $responsesMap */
        $responsesMap = new \SplObjectStorage();
        foreach ($batchItems as $item) {
            try {
                $response = $requestCallback($client, $item);
                $responsesMap->attach($response, $item);
            } catch (\Throwable $e) {
                $this->logger->error("Échec de préparation de requête: {$e->getMessage()}");
                $failedItems[] = $item;
            }
        }

        return $responsesMap;
    }

    /**
     * @param \SplObjectStorage<ResponseInterface, T> $responsesMap
     * @param callable(ResponseInterface): iterable<R> $responseCallback
     * @param array<T> $failedItems
     *
     * @return \Generator<R>
     */
    private function streamBatchResponses(
        HttpClientInterface $client,
        \SplObjectStorage $responsesMap,
        callable $responseCallback,
        array &$failedItems
    ): \Generator {
        $responsesArray = iterator_to_array($responsesMap);
        /** @var \SplObjectStorage<ResponseInterface, mixed> $processedResponses */
        $processedResponses = new \SplObjectStorage();

        try {
            foreach ($client->stream($responsesArray) as $response => $chunk) {
                try {
                    if ($chunk->isTimeout()) {
                        throw new \Exception('Timeout détecté sur le chunk');
                    }

                    if ($chunk->isLast()) {
                        $processedResponses->attach($response);
                        yield from $responseCallback($response);
                    }
                } catch (\Throwable $e) {
                    $processedResponses->attach($response);
                    $this->handleFailure($response, $responsesMap, $failedItems, $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            $this->handleGlobalStreamCrash(
                $responsesArray,
                $processedResponses,
                $responsesMap,
                $failedItems,
                $e->getMessage()
            );
        }
    }

    /**
     * @param array<T> $failedItems
     * @param callable(HttpClientInterface, T): ResponseInterface $req
     * @param callable(ResponseInterface): iterable<R> $res
     *
     * @return \Generator<R>
     */
    private function handleRetries(
        HttpClientInterface $client,
        string $api,
        array $failedItems,
        callable $req,
        callable $res,
        int $retryLevel
    ): \Generator {
        if (empty($failedItems)) {
            return;
        }

        if ($retryLevel < 2) {
            $this->logger->warning(count($failedItems) . ' requêtes échouées. Lancement rattrapage...');
            yield from $this->processInBatches($client, $api, $failedItems, 2, $req, $res, $retryLevel + 1);
        } else {
            foreach ($failedItems as $item) {
                $this->logger->error(
                    "ABANDON DÉFINITIF : Impossible de récupérer les données pour l'item : " . json_encode($item)
                );
            }
        }
    }

    /**
     * @param array<ResponseInterface> $responses
     * @param \SplObjectStorage<ResponseInterface, mixed> $processed
     * @param \SplObjectStorage<ResponseInterface, T> $map
     * @param array<T> $failedItems
     */
    private function handleGlobalStreamCrash(
        array $responses,
        \SplObjectStorage $processed,
        \SplObjectStorage $map,
        array &$failedItems,
        string $msg
    ): void {
        foreach ($responses as $response) {
            if (! $processed->contains($response)) {
                $this->handleFailure($response, $map, $failedItems, "Crash itérateur: {$msg}");
            }
        }
    }

    /**
     * @param \SplObjectStorage<ResponseInterface, T> $responsesMap
     * @param array<T> $failedItems
     *
     * @param-out array<T> $failedItems
     */
    private function handleFailure(
        ResponseInterface $response,
        \SplObjectStorage $responsesMap,
        array &$failedItems,
        string $error
    ): void {
        $item = $responsesMap[$response] ?? null;
        $url = $response->getInfo('url') ?? 'URL inconnue';

        $this->logger->warning("Échec sur {$url}. Ajouté au rattrapage. Erreur: {$error}");

        if ($item) {
            $failedItems[] = $item;
        }

        try {
            $response->cancel();
        } catch (\Throwable) {
        }
    }

    private function cleanup(string $api, int $batchIndex): void
    {
        gc_collect_cycles();
        $usage = round(memory_get_usage(true) / 1024 / 1024, 2);
        $this->logger->info("Mémoire après nettoyage API {$api} Batch {$batchIndex} : {$usage} MB");
    }
}
