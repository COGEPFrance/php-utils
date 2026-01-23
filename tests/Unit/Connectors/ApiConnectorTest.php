<?php

namespace Unit\Connectors;

use Cogep\PhpUtils\Connectors\ApiConnector;
use Cogep\PhpUtils\Tests\Connectors\AbstractApiConnectorTestCase;

class ApiConnectorTest extends AbstractApiConnectorTestCase
{
    protected function createConnector(): ApiConnector
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
