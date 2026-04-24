<?php

namespace Cogep\PhpUtils\Enums;

enum ErrorCodeEnum: string
{
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case NOT_FOUND = 'NOT_FOUND';
    case ALREADY_EXISTS = 'ALREADY_EXISTS';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case INVALID_INPUT = 'INVALID_INPUT';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
}
