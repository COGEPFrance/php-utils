<?php

namespace Cogep\PhpUtils\Classes\Responses;

class ResponseMetadataDto
{
    public string $timestamp;

    public function __construct()
    {
        $this->timestamp = new \DateTimeImmutable()
            ->format(\DateTimeInterface::ATOM);
    }
}
