<?php

namespace Cogep\PhpUtils\Config;

use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands\RabbitMqCommandQueueHandler;

/**
 * @phpstan-consistent-constructor
 */
readonly class Settings
{
    public function __construct(
        public string $appName,
        public string $appVersion,
        public string $appEnv,
        public string $rabbitHost,
        public int $rabbitPort,
        public string $rabbitUser,
        public string $rabbitPass,
        public string $rabbitQueueCmd,
        public string $rabbitQueueDlq,
        public int $appPort = 8000,
        public int $rabbitPrefetch = 1,
    ) {
    }

    public static function fromEnv(): static
    {
        return new static(
            appName: $_ENV['APP_NAME'],
            appVersion: $_ENV['APP_VERSION'],
            appEnv: $_ENV['APP_ENV'],
            rabbitHost: $_ENV['RABBITMQ_HOST'],
            rabbitPort: (int) ($_ENV['RABBITMQ_PORT']),
            rabbitUser: $_ENV['RABBITMQ_USER'],
            rabbitPass: $_ENV['RABBITMQ_PASSWORD'],
            rabbitQueueCmd: $_ENV['RABBITMQ_QUEUE_COMMANDS'],
            rabbitQueueDlq: $_ENV['RABBITMQ_QUEUE_DLQ'],
            appPort: (int) ($_ENV['APP_PORT'] ?? 8000),
            rabbitPrefetch: (int) ($_ENV['RABBITMQ_PREFETCH_COUNT'] ?? 1),
        );
    }

    /**
     * @return array<string, string>
     */
    public function getQueueMapping(): array
    {
        return [
            $this->rabbitQueueCmd => RabbitMqCommandQueueHandler::class,
        ];
    }

    protected static function getRequiredEnv(string $key): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        if (empty($value)) {
            throw new \RuntimeException(sprintf('CRITICAL: Environment variable "%s" is missing or empty.', $key));
        }

        return (string) $value;
    }
}
