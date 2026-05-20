<?php

namespace Cogep\PhpUtils\Tests\Unit\Connectors\Configs;

use Cogep\PhpUtils\Connectors\Configs\ApiClientConnectorConfig;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use InvalidArgumentException;

class ApiClientConnectorConfigTest extends BaseMockeryTestCase
{
    public function testExceptionWhenMissingCredentials(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('For API Client connection you must have a clientId and clientSecret');
        new ApiClientConnectorConfig(baseUrl: 'test');
    }
}
