<?php

namespace Cogep\PhpUtils\Adapters\Input\Dtos\Responses;

use Cogep\PhpUtils\Adapters\Input\ErrorCodeEnum;

class StandardResponseDto
{
    public function __construct(
        public string $status,
        public ?ResponseMetadataDto $meta,
        public mixed $data = null,
        public ?ResponseErrorDetailDto $error = null,
    ) {
    }

    public static function success(mixed $data): self
    {
        return new self(status: 'success', meta: new ResponseMetadataDto(), data: $data);
    }

    public static function error(ErrorCodeEnum $code, string $message, ?string $detail = null): self
    {
        return new self(
            status: 'error',
            meta: new ResponseMetadataDto(),
            error: new ResponseErrorDetailDto($code, $message, $detail)
        );
    }
}
