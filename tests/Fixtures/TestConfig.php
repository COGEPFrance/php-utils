<?php

namespace Cogep\PhpUtils\Tests\Fixtures;

use Cogep\PhpUtils\Config\Settings;

readonly class TestConfig extends Settings
{
    public function __construct()
    {
        parent::__construct(
            appName: 'test-app',
            appVersion: '1.0.0',
            appEnv: 'test',
            rabbitHost: 'localhost',
            rabbitPort: 5672,
            rabbitUser: 'guest',
            rabbitPass: 'guest',
            rabbitQueueCmd: 'test_queue',
            rabbitQueueDlq: 'test-dlq',
            azureStorageAccount: 'https://test-storage.blob.core.windows.net',
            azureBlobSasToken: null,
        );
    }

    public static function fromEnv(): static
    {
        return new self();
    }

    public function getQueueMapping(): array
    {
        return [
            'queue_1' => 'Handler',
            'test_queue' => 'TestHandler',
        ];
    }
}
