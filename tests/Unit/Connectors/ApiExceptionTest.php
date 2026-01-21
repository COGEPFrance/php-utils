<?php

namespace Unit\Connectors;

use Cogep\PhpUtils\Connectors\ApiException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiExceptionTest extends TestCase
{
    /**
     * On crée une classe concrète pour tester la logique de l'abstract.
     */
    private function createException($response): ApiException
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

    /**
     */
    public function testConstructorParsesJsonResponseSuccessfully(): void
    {
        $body = ['error' => 'Something went wrong', 'code' => 42];
        $response = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(400);
        $response->shouldReceive('toArray')->with(false)->andReturn($body);

        $exception = $this->createException($response);

        $this->assertEquals(400, $exception->getCode());
        $this->assertStringContainsString('Error TEST_API (400)', $exception->getMessage());
        $this->assertIsArray($exception->details);
        $this->assertEquals($body["code"], $exception->details['code']);
    }

    public function testConstructorHandlesInvalidJsonGracefully(): void
    {
        $response = new MockResponse(json_encode(['error' => 'Something went wrong', 'code' => 42]), [
            'http_code' => 400,
            'response_headers' => ['Content-Type' => 'application/json']
        ]);
        new MockHttpClient($response);

        $exception = $this->createException($response);

        $this->assertStringContainsString("Impossible de lire le corps de la réponse d'erreur", $exception->getMessage());
        $this->assertNull($exception->details);
    }
}