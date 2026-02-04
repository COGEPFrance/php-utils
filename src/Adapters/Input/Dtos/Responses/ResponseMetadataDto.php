<?php

namespace Cogep\PhpUtils\Adapters\Input\Dtos\Responses;

class ResponseMetadataDto
{
    public string $timestamp;

    public function __construct()
    {
        $this->timestamp = new \DateTimeImmutable()
            ->format(\DateTimeInterface::ATOM);
    }
}
