<?php

namespace Cogep\PhpUtils\Config;

use Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client\AzureBlobConfig;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands\RabbitMqCommandQueueHandler;

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
        public ?string $azureStorageAccount,
        public ?string $azureBlobContainer,
        public ?string $azureBlobSasUrl,
        public int $appPort = 8000,
        public int $rabbitPrefetch = 1,
    ) {
    }

    public static function fromEnv(): static
    {
        return new static(
            appName: self::getRequiredEnv('APP_NAME'),
            appVersion: self::getRequiredEnv('APP_VERSION'),
            appEnv: self::getRequiredEnv('APP_ENV'),
            rabbitHost: self::getRequiredEnv('RABBITMQ_HOST'),
            rabbitPort: (int) self::getRequiredEnv('RABBITMQ_PORT'),
            rabbitUser: self::getRequiredEnv('RABBITMQ_USER'),
            rabbitPass: self::getRequiredEnv('RABBITMQ_PASSWORD'),
            rabbitQueueCmd: self::getRequiredEnv('RABBITMQ_QUEUE_COMMANDS'),
            rabbitQueueDlq: self::getRequiredEnv('RABBITMQ_QUEUE_DLQ'),
            appPort: (int) self::getDefaultEnv('APP_PORT', '8000'),
            rabbitPrefetch: (int) self::getDefaultEnv('RABBITMQ_PREFETCH_COUNT', '1'),
            azureStorageAccount: self::getDefaultEnv('AZURE_STORAGE_ACCOUNT', null),
            azureBlobContainer: self::getDefaultEnv('AZURE_BLOB_CONTAINER', null),
            azureBlobSasUrl: self::getDefaultEnv('AZURE_BLOB_SAS_URL', null),
        );
    }

    /**
     * @return array<string, class-string>
     */
    public function getQueueMapping(): array
    {
        return [
            $this->rabbitQueueCmd => RabbitMqCommandQueueHandler::class,
        ];
    }

    public function getAzureBlobConfig(): AzureBlobConfig
    {
        return new AzureBlobConfig(
            containerName: $this->azureBlobContainer,
            accountUrl: $this->azureStorageAccount,
            sasUrl: $this->azureBlobSasUrl,
        );
    }

    protected static function getDefaultEnv(string $key, string|null $default): string
    {
        return $_ENV[$key] ?? (getenv($key) !== false ? getenv($key) : $default);
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
