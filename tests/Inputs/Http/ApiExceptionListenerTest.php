<?php

namespace Inputs\Http;

use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Cogep\PhpUtils\Inputs\Http\ApiExceptionListener;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiExceptionListenerTest extends MockeryTestCase
{
    private ApiExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ApiExceptionListener();
    }

    public function testOnKernelExceptionIgnoresNonApiRequests()
    {
        $event = $this->createExceptionEvent(new \Exception('Error'), '/web/not-api');

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelExceptionHandlesDomainException()
    {
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

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString(ErrorCodeEnum::FORBIDDEN->value, $response->getContent());
    }

    public function testOnKernelExceptionHandlesFallbackToInternalError()
    {
        $exception = new \Exception('Unexpected crash');
        $event = $this->createExceptionEvent($exception, '/api/crash');

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(500, $response->getStatusCode());

        $this->assertStringContainsString(ErrorCodeEnum::INTERNAL_ERROR->value, $response->getContent());
    }

    public function testGetEnumFromHttpCodeWithUnmappedStatus()
    {
        $exception = new HttpException(418, 'Teapot');

        $event = $this->createExceptionEvent($exception, '/api/teapot');

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(418, $response->getStatusCode());

        $this->assertStringContainsString(ErrorCodeEnum::INTERNAL_ERROR->value, $response->getContent());
    }

    private function createExceptionEvent(\Throwable $exception, string $path): ExceptionEvent
    {
        $kernel = Mockery::mock(HttpKernelInterface::class);
        $request = Request::create($path);

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
