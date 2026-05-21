<?php

namespace Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client;

class AzureBlobConfig
{
    public function __construct(
        public string $accountUrl,
        public ?string $sasToken
    ) {
    }
}
