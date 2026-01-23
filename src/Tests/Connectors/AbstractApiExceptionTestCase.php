<?php

namespace Cogep\PhpUtils\Tests\Connectors;

use Cogep\PhpUtils\Connectors\ApiException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractApiExceptionTestCase extends TestCase
{
    public function testConstructorParsesJsonResponseSuccessfully(): void
    {
        $body = $this->getValidResponseBody();

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(400);

        $response->method('toArray')
            ->with(false)
            ->willReturn($body);

        $exception = $this->createException($response);

        $this->assertEquals(400, $exception->getCode());
        $this->assertStringContainsString($this->getExpectedErrorMessage(), $exception->getMessage());

    }

    public function testConstructorHandlesInvalidJsonGracefully(): void
    {
        $content = 'invalid json';

        $response = new MockResponse($content, [
            'http_code' => 400,
            'response_headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $exception = $this->createException($response);

        $this->assertStringContainsString(
            "Impossible de lire le corps de la réponse d'erreur",
            $exception->getMessage()
        );
        $this->assertNull($exception->details);
    }

    abstract protected function createException(ResponseInterface $response): ApiException;

    /**
     * @return array<string, mixed>
     */
    abstract protected function getValidResponseBody(): array;

    abstract protected function getExpectedErrorMessage(): string;
}
