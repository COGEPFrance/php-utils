<?php


namespace Unit\Connectors\Configs;
use Cogep\PhpUtils\Connectors\Configs\ApiClientConnectorConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ApiClientConnectorConfigTest extends TestCase
{
    public function testExceptionWhenMissingCredentials(): void
{
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('For API Client connection you must have a clientId and clientSecret');
    new ApiClientConnectorConfig(
        baseUrl:'test',
    );
}
}
