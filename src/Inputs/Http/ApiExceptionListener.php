<?php

namespace Cogep\PhpUtils\Inputs\Http;

use Cogep\PhpUtils\Classes\Responses\StandardResponseDto;
use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsEventListener(event: 'kernel.exception', method: 'onKernelException')]
class ApiExceptionListener
{
    public const array CODE_ERROR_TO_HTTP_CODE = [
        ErrorCodeEnum::VALIDATION_ERROR->value => 422,
        ErrorCodeEnum::NOT_FOUND->value => 404,
        ErrorCodeEnum::ALREADY_EXISTS->value => 409,
        ErrorCodeEnum::INTERNAL_ERROR->value => 500,
        ErrorCodeEnum::INVALID_INPUT->value => 400,
        ErrorCodeEnum::UNAUTHORIZED->value => 401,
        ErrorCodeEnum::FORBIDDEN->value => 403,
    ];

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        [$errorCode, $httpCode] = match (true) {
            $exception instanceof DomainException => [
                $exception->getErrorCode(),
                self::CODE_ERROR_TO_HTTP_CODE[$exception->getErrorCode()->value],
            ],
            $exception instanceof HttpExceptionInterface => [
                $this->getEnumFromHttpCode($exception->getStatusCode()),
                $exception->getStatusCode(),
            ],
            default => [ErrorCodeEnum::INTERNAL_ERROR, 500],
        };

        $responseDto = StandardResponseDto::error(code: $errorCode, message: $exception->getMessage());

        $response = new JsonResponse($responseDto, $httpCode, [], false);
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $event->setResponse($response);
    }

    private function getEnumFromHttpCode(int $statusCode): ErrorCodeEnum
    {
        $enumValue = array_search($statusCode, self::CODE_ERROR_TO_HTTP_CODE, true);

        if ($enumValue !== false) {
            return ErrorCodeEnum::tryFrom((string) $enumValue);
        }

        return ErrorCodeEnum::INTERNAL_ERROR;
    }
}
