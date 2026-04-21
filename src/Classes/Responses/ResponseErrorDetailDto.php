<?php

namespace Cogep\PhpUtils\Classes\Responses;

use Cogep\PhpUtils\Enums\ErrorCodeEnum;

class ResponseErrorDetailDto
{
    public function __construct(
        public ErrorCodeEnum $code,
        public string $message,
        public ?string $detail = null,
    ) {
    }
}
