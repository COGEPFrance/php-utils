<?php


namespace Cogep\PhpUtils\Connectors\Configs;
class ApiClientConnectorConfig
{
    public function __construct(
        public string $baseUrl,
        public ?string $clientId = null,
        public ?string $clientSecret = null,
        public ?string $accessToken = null,
        public ?string $authUrl = null,
    ){
        if (!($this->clientId && $this->clientSecret)) {
            throw new \InvalidArgumentException('For API Client connection you must have a clientId and clientSecret');
        }
    }
}
