<?php

namespace Cogep\PhpUtils\Config;

use Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client\AzureBlobConfig;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands\RabbitMqCommandQueueHandler;
use Cogep\PhpUtils\Inputs\Rabbitmq\RabbitMqConfig;

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
        public string $rabbitDlqExchange,
        public string $rabbitDlqRoutingKey,
        public string $rabbitExchange,
        public string $azureStorageUrl,
        public ?string $azureBlobSasToken,
        public int $appPort = 8000,
        public int $rabbitPrefetch = 1,
    ) {
    }

    /**
     * Crée une instance Settings à partir des variables d'environnement.
     * Utilise new self() pour éviter les problèmes d'héritage.
     */
    public static function fromEnv(): self
    {
        return new self(
            appName: self::getRequiredEnv('APP_NAME'),
            appVersion: self::getRequiredEnv('APP_VERSION'),
            appEnv: self::getRequiredEnv('APP_ENV'),
            rabbitHost: self::getRequiredEnv('RABBITMQ_HOST'),
            rabbitPort: (int) self::getRequiredEnv('RABBITMQ_PORT'),
            rabbitUser: self::getRequiredEnv('RABBITMQ_USER'),
            rabbitPass: self::getRequiredEnv('RABBITMQ_PASSWORD'),
            rabbitQueueCmd: self::getRequiredEnv('RABBITMQ_QUEUE_COMMANDS'),
            rabbitQueueDlq: self::getRequiredEnv('RABBITMQ_QUEUE_DLQ'),
            rabbitDlqExchange: self::getRequiredEnv('RABBITMQ_DLX'),
            rabbitDlqRoutingKey: self::getRequiredEnv('RABBITMQ_DLK'),
            rabbitExchange: self::getRequiredEnv('RABBITMQ_EXCHANGE_NAME'),
            azureStorageUrl: self::getRequiredEnv('AZURE_STORAGE_URL'),
            azureBlobSasToken: self::getDefaultEnv('AZURE_BLOB_SAS_TOKEN', null),
            appPort: (int) self::getDefaultEnv('APP_PORT', '8000'),
            rabbitPrefetch: (int) self::getDefaultEnv('RABBITMQ_PREFETCH_COUNT', '1'),
        );
    }

    public function getAzureBlobConfig(): AzureBlobConfig
    {
        return new AzureBlobConfig(accountUrl: $this->azureStorageUrl, sasToken: $this->azureBlobSasToken);
    }

    public function getRabbitMqConfig(): RabbitMqConfig
    {
        return new RabbitMqConfig(
            dl_queue: $this->rabbitQueueDlq,
            dl_exchange: $this->rabbitDlqExchange,
            dl_routing_key: $this->rabbitDlqRoutingKey,
            exchange: $this->rabbitExchange,
            queue_handler_mapping: $this->getQueueMapping(),
        );
    }

    /**
     * Chaque classe enfant doit implémenter sa propre méthode statique fromEnv(),
     * et appeler explicitement le constructeur parent avec les bons arguments.
     * Cela évite les problèmes liés à l'utilisation de new static() dans le parent.
     */
    // public static function fromEnv(): static { ... } // À implémenter dans chaque enfant

    /**
     * @return array<string, class-string>
     */
    protected function getQueueMapping(): array
    {
        return [
            $this->rabbitQueueCmd => RabbitMqCommandQueueHandler::class,
        ];
    }

    protected static function getDefaultEnv(string $key, string|null $default): ?string
    {
        $getEnv = getenv($key);
        return $_ENV[$key] ?? ($getEnv !== false ? $getEnv : $default);
    }

    protected static function getRequiredEnv(string $key): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === '') {
            throw new \RuntimeException(sprintf('CRITICAL: Environment variable "%s" is missing or empty.', $key));
        }

        return (string) $value;
    }
}
