<?php

namespace Cogep\PhpUtils\Tests\Unit\Config;

use Cogep\PhpUtils\Config\Settings;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands\RabbitMqCommandQueueHandler;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;

class SettingsTest extends BaseMockeryTestCase
{
    private array $backupEnv;

    protected function setUp(): void
    {
        $this->backupEnv = $_ENV;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->backupEnv;
    }

    public function testFromEnvCreatesInstanceWithAllRequiredVariables(): void
    {
        $_ENV = $this->getRequiredEnvArray();

        $settings = Settings::fromEnv();

        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertSame('my-app', $settings->appName);
        $this->assertSame(5672, $settings->rabbitPort);
        $this->assertSame('https://storage.url', $settings->azureStorageAccount);
        $this->assertNull($settings->azureBlobSasToken);
        $this->assertSame(8000, $settings->appPort);
        $this->assertSame(1, $settings->rabbitPrefetch);
    }

    public function testFromEnvUsesOptionalVariablesIfPresent(): void
    {
        $_ENV = $this->getRequiredEnvArray();
        $_ENV['APP_PORT'] = '9000';
        $_ENV['RABBITMQ_PREFETCH_COUNT'] = '50';
        $_ENV['AZURE_BLOB_SAS_TOKEN'] = 'token123';

        $settings = Settings::fromEnv();

        $this->assertSame(9000, $settings->appPort);
        $this->assertSame(50, $settings->rabbitPrefetch);
        $this->assertSame('token123', $settings->azureBlobSasToken);
    }

    public function testGetQueueMappingReturnsCorrectHandler(): void
    {
        $_ENV = $this->getRequiredEnvArray();
        $_ENV['RABBITMQ_QUEUE_COMMANDS'] = 'specific_queue';

        $settings = Settings::fromEnv();
        $mapping = $settings->getQueueMapping();

        $this->assertArrayHasKey('specific_queue', $mapping);
        $this->assertSame(RabbitMqCommandQueueHandler::class, $mapping['specific_queue']);
    }

    public function testFromEnvThrowsExceptionWhenVariableIsMissing(): void
    {
        $_ENV = []; // On vide tout

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment variable "APP_NAME" is missing');

        Settings::fromEnv();
    }

    private function getRequiredEnvArray(): array
    {
        return [
            'APP_NAME' => 'my-app',
            'APP_VERSION' => '1.2.3',
            'APP_ENV' => 'prod',
            'RABBITMQ_HOST' => 'rabbit.host',
            'RABBITMQ_PORT' => '5672',
            'RABBITMQ_USER' => 'user',
            'RABBITMQ_PASSWORD' => 'pass',
            'RABBITMQ_QUEUE_COMMANDS' => 'q_cmd',
            'RABBITMQ_QUEUE_DLQ' => 'q_dlq',
            'AZURE_STORAGE_URL' => 'https://storage.url',
        ];
    }
}
