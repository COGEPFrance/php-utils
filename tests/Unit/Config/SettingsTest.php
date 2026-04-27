<?php

namespace Cogep\PhpUtils\Tests\Unit\Config;

use Cogep\PhpUtils\Config\Settings;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands\RabbitMqCommandQueueHandler;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
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
        // Simulation d'un .env complet
        $_ENV['APP_NAME'] = 'my-app';
        $_ENV['APP_VERSION'] = '1.2.3';
        $_ENV['APP_ENV'] = 'prod';
        $_ENV['RABBITMQ_HOST'] = 'rabbit.host';
        $_ENV['RABBITMQ_PORT'] = '5672';
        $_ENV['RABBITMQ_USER'] = 'user';
        $_ENV['RABBITMQ_PASSWORD'] = 'pass';
        $_ENV['RABBITMQ_QUEUE_COMMANDS'] = 'q_cmd';
        $_ENV['RABBITMQ_QUEUE_DLQ'] = 'q_dlq';

        $settings = Settings::fromEnv();

        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertSame('my-app', $settings->appName);
        $this->assertSame(5672, $settings->rabbitPort);
        // Test des valeurs par défaut si absentes de $_ENV
        $this->assertSame(8000, $settings->appPort);
        $this->assertSame(1, $settings->rabbitPrefetch);
    }

    public function testFromEnvUsesOptionalVariablesIfPresent(): void
    {
        $this->fillRequiredEnv();

        // On surcharge les optionnels
        $_ENV['APP_PORT'] = '9000';
        $_ENV['RABBITMQ_PREFETCH_COUNT'] = '50';

        $settings = Settings::fromEnv();

        $this->assertSame(9000, $settings->appPort);
        $this->assertSame(50, $settings->rabbitPrefetch);
    }

    public function testGetQueueMappingReturnsCorrectHandler(): void
    {
        $this->fillRequiredEnv();
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

    /**
     * Helper pour remplir le minimum requis
     */
    private function fillRequiredEnv(): void
    {
        $_ENV['APP_NAME'] = 'test';
        $_ENV['APP_VERSION'] = '1.0';
        $_ENV['APP_ENV'] = 'dev';
        $_ENV['RABBITMQ_HOST'] = 'localhost';
        $_ENV['RABBITMQ_PORT'] = '5672';
        $_ENV['RABBITMQ_USER'] = 'u';
        $_ENV['RABBITMQ_PASSWORD'] = 'p';
        $_ENV['RABBITMQ_QUEUE_COMMANDS'] = 'q';
        $_ENV['RABBITMQ_QUEUE_DLQ'] = 'd';
    }
}
