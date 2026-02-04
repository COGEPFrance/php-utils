<?php

namespace Cogep\PhpUtils\Adapters\Input\Dtos\Responses;

use Cogep\PhpUtils\Adapters\Input\ErrorCodeEnum;

class ResponseErrorDetailDto
{
    public function __construct(
        public ErrorCodeEnum $code,
        public string $message,
        public ?string $detail = null,
    ) {
    }
}
