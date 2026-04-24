<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Cli;

use Cogep\PhpUtils\Classes\DTOInterface;
use Cogep\PhpUtils\Classes\Responses\StandardResponseDto;
use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Helpers\EntityValidator;
use Cogep\PhpUtils\Inputs\Cli\ConsoleBusCommand;
use Cogep\PhpUtils\Inputs\Cli\ConsoleCommandHelper;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConsoleBusCommandTest extends MockeryTestCase
{
    private $serializer;

    private $denormalizer;

    private $logger;

    private $helper;

    private $bus;

    private $validator;

    private string $dtoClass = 'MyDtoClass';

    protected function setUp(): void
    {
        $this->serializer = Mockery::mock(SerializerInterface::class);
        $this->denormalizer = Mockery::mock(DenormalizerInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->helper = Mockery::mock(ConsoleCommandHelper::class);
        $this->bus = Mockery::mock(MessageBusInterface::class);
        $this->validator = Mockery::mock(EntityValidator::class);
    }

    public function testExecuteSuccess(): void
    {
        $mockDto = Mockery::mock(DTOInterface::class);
        $responseDto = StandardResponseDto::success(null);

        $this->helper->shouldReceive('addOptionsFromDto')
            ->once();
        $this->helper->shouldReceive('setupJsonLogging')
            ->once();
        $this->denormalizer->shouldReceive('denormalize')
            ->andReturn($mockDto);
        $this->validator->shouldReceive('validate')
            ->once();

        $envelope = new Envelope($mockDto, [new HandledStamp($responseDto, 'handler')]);
        $this->bus->shouldReceive('dispatch')
            ->andReturn($envelope);
        $this->serializer->shouldReceive('serialize')
            ->andReturn('{"status":"success"}');

        $command = new class(
            'test:bus',
            $this->dtoClass,
            $this->serializer,
            $this->denormalizer,
            $this->logger,
            $this->helper,
            $this->bus,
            $this->validator
        ) extends ConsoleBusCommand {
            protected function terminate(int $code): void
            {
            }
        };

        $input = Mockery::mock(InputInterface::class)->shouldIgnoreMissing();
        $input->shouldReceive('getOption')
            ->with('json')
            ->andReturn(true);
        $input->shouldReceive('getOptions')
            ->andReturn([
                'opt' => 'val',
            ]);

        $output = Mockery::mock(OutputInterface::class)->shouldIgnoreMissing();

        $this->assertEquals(Command::SUCCESS, $command->run($input, $output));
    }

    public function testExecuteFailure(): void
    {
        $mockDto = Mockery::mock(DTOInterface::class);
        $responseDto = StandardResponseDto::error(ErrorCodeEnum::INTERNAL_ERROR, 'Error');

        $this->helper->shouldReceive('addOptionsFromDto')
            ->once();
        $this->helper->shouldReceive('interactivelyFillDto')
            ->once();
        $this->denormalizer->shouldReceive('denormalize')
            ->andReturn($mockDto);
        $this->validator->shouldReceive('validate')
            ->once();

        $envelope = new Envelope($mockDto, [new HandledStamp($responseDto, 'handler')]);
        $this->bus->shouldReceive('dispatch')
            ->andReturn($envelope);
        $this->serializer->shouldReceive('serialize')
            ->andReturn('{"status":"error"}');

        $command = new class(
            'test:bus',
            $this->dtoClass,
            $this->serializer,
            $this->denormalizer,
            $this->logger,
            $this->helper,
            $this->bus,
            $this->validator
        ) extends ConsoleBusCommand {
            protected function terminate(int $code): void
            {
            }
        };

        $input = Mockery::mock(InputInterface::class)->shouldIgnoreMissing();
        $input->shouldReceive('getOption')
            ->with('json')
            ->andReturn(false);
        $input->shouldReceive('getOptions')
            ->andReturn([]);

        $output = new BufferedOutput();
        $this->assertEquals(Command::FAILURE, $command->run($input, $output));
    }
}
