<?php

namespace Cogep\PhpUtils\Classes\Responses;

use Cogep\PhpUtils\Enums\ErrorCodeEnum;

class StandardResponseDto
{
    public function __construct(
        public ResponseStatusEnum $status,
        public ?ResponseMetadataDto $meta,
        public mixed $data = null,
        public ?ResponseErrorDetailDto $error = null,
    ) {
    }

    public static function success(mixed $data): self
    {
        return new self(status: ResponseStatusEnum::SUCCESS, meta: new ResponseMetadataDto(), data: $data);
    }

    public static function error(ErrorCodeEnum $code, string $message, ?string $detail = null): self
    {
        return new self(
            status: ResponseStatusEnum::ERROR,
            meta: new ResponseMetadataDto(),
            error: new ResponseErrorDetailDto($code, $message, $detail)
        );
    }
}
