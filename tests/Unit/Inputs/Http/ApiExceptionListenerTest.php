<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Http;

use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Cogep\PhpUtils\Inputs\Http\ApiExceptionListener;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
// ...existing code...
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiExceptionListenerTest extends BaseMockeryTestCase
{
    private ApiExceptionListener $listener;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->listener = new ApiExceptionListener($this->logger);
    }

    public function testOnKernelExceptionHandlesDomainException()
    {
        $this->logger->shouldReceive('error')
            ->once();
        $exception = new DomainException(ErrorCodeEnum::VALIDATION_ERROR, 'validation error');
        $event = $this->createExceptionEvent($exception, '/api/test');

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString(ErrorCodeEnum::VALIDATION_ERROR->value, $response->getContent());
    }

    public function testOnKernelExceptionHandlesHttpException()
    {
        $exception = new HttpException(403, 'Forbidden');
        $event = $this->createExceptionEvent($exception, '/api/resource');
        $this->logger->shouldReceive('error')
            ->once();

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString(ErrorCodeEnum::FORBIDDEN->value, $response->getContent());
    }

    public function testOnKernelExceptionHandlesFallbackToInternalError()
    {
        $exception = new \Exception('Unexpected crash');
        $event = $this->createExceptionEvent($exception, '/api/crash');
        $this->logger->shouldReceive('error')
            ->once();

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(500, $response->getStatusCode());

        $this->assertStringContainsString(ErrorCodeEnum::INTERNAL_ERROR->value, $response->getContent());
    }

    public function testGetEnumFromHttpCodeWithUnmappedStatus()
    {
        $exception = new HttpException(418, 'Teapot');

        $event = $this->createExceptionEvent($exception, '/api/teapot');
        $this->logger->shouldReceive('error')
            ->once();

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(418, $response->getStatusCode());

        $this->assertStringContainsString(ErrorCodeEnum::INTERNAL_ERROR->value, $response->getContent());
    }

    private function createExceptionEvent(\Throwable $exception, string $path): ExceptionEvent
    {
        $kernel = \Mockery::mock(HttpKernelInterface::class);
        $request = Request::create($path);

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
