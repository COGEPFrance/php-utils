<?php


namespace Cogep\PhpUtils\Connectors\Configs;
class CacheConnectorConfig
{
    public function __construct(
        public string $cacheName,
        public int    $cacheStoreTime
    )
    {
    }
}
