<?php

namespace Unit\Connectors;

use Cogep\PhpUtils\Connectors\ApiException;
use Cogep\PhpUtils\Tests\Connectors\AbstractApiExceptionTestCase;

class ApiExceptionTest extends AbstractApiExceptionTestCase
{
    /**
     * On crée une classe concrète pour tester la logique de l'abstract.
     */
    protected function createException($response): ApiException
    {
        return new class($response) extends ApiException {
            protected function extractErrorMessage(mixed $data): ?string
            {
                return $data['error'] ?? null;
            }

            protected function getApiName(): string
            {
                return 'TEST_API';
            }
        };
    }

    protected function getValidResponseBody(): array
    {
        return [
            'error' => 'Something went wrong',
            'code' => 42,
            'status' => 'fail',
        ];
    }

    protected function getExpectedErrorMessage(): string
    {
        return 'Something went wrong';
    }
}
