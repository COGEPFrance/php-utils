<?php

namespace Cogep\PhpUtils\Tests\Unit\Config;

use Cogep\PhpUtils\Config\Settings;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;

class SettingsTest extends BaseMockeryTestCase
{
    private array $backupEnv;

    private array $backupGetenv;

    protected function setUp(): void
    {
        $this->backupEnv = $_ENV;
        $this->backupGetenv = [];
        foreach (array_keys($this->getRequiredEnvArray()) as $key) {
            $this->backupGetenv[$key] = getenv($key);
        }
    }

    protected function tearDown(): void
    {
        $_ENV = $this->backupEnv;
        foreach ($this->backupGetenv as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv("{$key}={$value}");
            }
        }
        parent::tearDown();
    }

    public function testFromEnvCreatesInstanceWithAllRequiredVariables(): void
    {
        $_ENV = $this->getRequiredEnvArray();

        $settings = Settings::fromEnv();

        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertSame('my-app', $settings->appName);
        $this->assertSame(5672, $settings->rabbitPort);
        $this->assertSame('https://storage.url', $settings->azureStorageUrl);
        $this->assertNull($settings->azureBlobSasToken);
        $this->assertSame(8000, $settings->appPort);
        $this->assertSame(1, $settings->rabbitPrefetch);

        // Nouveaux attributs RabbitMQ
        $this->assertSame('q_cmd', $settings->rabbitQueueCmd);
        $this->assertSame('q_dlq', $settings->rabbitQueueDlq);
        $this->assertSame('exchange', $settings->rabbitExchange);
        $this->assertSame('dlq', $settings->rabbitDlqExchange);
        $this->assertSame('dlx', $settings->rabbitDlqRoutingKey);
    }

    public function testGetRabbitMqConfig(): void
    {
        $_ENV = $this->getRequiredEnvArray();
        $_ENV['RABBITMQ_QUEUE_COMMANDS'] = 'specific_queue';
        $_ENV['RABBITMQ_QUEUE_DLQ'] = 'dlq_queue';
        $_ENV['RABBITMQ_EXCHANGE_NAME'] = 'main_exchange';
        $_ENV['RABBITMQ_DLX'] = 'dlx_exchange';
        $_ENV['RABBITMQ_DLK'] = 'dlx_routing';

        $settings = Settings::fromEnv();
        $config = $settings->getRabbitMqConfig();

        $this->assertSame('dlq_queue', $config->dl_queue);
        $this->assertSame('dlx_exchange', $config->dl_exchange);
        $this->assertSame('dlx_routing', $config->dl_routing_key);
        $this->assertSame('main_exchange', $config->exchange);
        $this->assertArrayHasKey('specific_queue', $config->queue_handler_mapping);
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

    public function testFromEnvThrowsExceptionWhenVariableIsMissing(): void
    {
        $_ENV = [];
        foreach (array_keys($this->getRequiredEnvArray()) as $key) {
            putenv($key); // supprime aussi de getenv()
        }

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
            'RABBITMQ_EXCHANGE_NAME' => 'exchange',
            'RABBITMQ_DLK' => 'dlx',
            'RABBITMQ_DLX' => 'dlq',
        ];
    }
}
