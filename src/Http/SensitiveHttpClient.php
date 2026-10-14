<?php

namespace Cogep\PhpUtils\Http;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[WithMonologChannel('sensitive')]
class SensitiveHttpClient implements HttpClientInterface
{
    use DecoratorTrait;

    public function __construct(LoggerInterface $logger, ?HttpClientInterface $client = null)
    {
        $this->client = $client ?? HttpClient::create();

        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }
}
