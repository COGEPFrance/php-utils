<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Cli;

use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Cogep\PhpUtils\Inputs\Cli\ConsoleExceptionListener;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use Mockery;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConsoleExceptionListenerTest extends BaseMockeryTestCase
{
    private $serializer;

    private $listener;

    private $logger;

    protected function setUp(): void
    {
        $this->serializer = \Mockery::mock(SerializerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->listener = new ConsoleExceptionListener($this->serializer, $this->logger);
    }

    public function testOnConsoleErrorIgnoresIfNoJsonOption()
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('hasParameterOption')
            ->with('--json')
            ->andReturn(false);
        $this->logger->shouldReceive('error')
            ->once();
        $event = new ConsoleErrorEvent($input, Mockery::mock(OutputInterface::class), new \Exception(), new Command(
            'test'
        ));

        $this->listener->onConsoleError($event);

        $this->serializer->shouldNotReceive('serialize');
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnConsoleErrorWritesJsonForDomainException()
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('hasParameterOption')
            ->with('--json')
            ->andReturn(true);
        $this->logger->shouldReceive('error')
            ->once();
        $exception = new DomainException(ErrorCodeEnum::VALIDATION_ERROR, 'Invalid data');
        $event = new ConsoleErrorEvent($input, Mockery::mock(OutputInterface::class), $exception, new Command(
            'test'
        ));

        $this->serializer->shouldReceive('serialize')
            ->once()
            ->andReturn('{"error": "VALIDATION_ERROR"}');

        $this->listener->onConsoleError($event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertEquals(1, $event->getExitCode());
    }

    public function testOnConsoleErrorFallbackToInternalError()
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('hasParameterOption')
            ->with('--json')
            ->andReturn(true);
        $this->logger->shouldReceive('error')
            ->once();
        $exception = new \Exception('Generic crash');
        $event = new ConsoleErrorEvent($input, Mockery::mock(OutputInterface::class), $exception, new Command(
            'test'
        ));
        $this->serializer->shouldReceive('serialize')
            ->with(
                Mockery::on(fn ($dto) => $dto->error->code === ErrorCodeEnum::INTERNAL_ERROR),
                Mockery::any(),
                Mockery::any()
            )
            ->once()
            ->andReturn('{"error": "INTERNAL_ERROR"}');

        $this->listener->onConsoleError($event);
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testOnConsoleTerminateStopsPropagationIfJson()
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('hasParameterOption')
            ->with('--json')
            ->andReturn(true);

        $event = new ConsoleTerminateEvent(new Command('test'), $input, Mockery::mock(OutputInterface::class), 0);

        $this->listener->onConsoleTerminate($event);

        $this->assertTrue($event->isPropagationStopped());
    }
}
