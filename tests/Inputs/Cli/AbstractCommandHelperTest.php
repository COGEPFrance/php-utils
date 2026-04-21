<?php

namespace Inputs\Cli;

use Cogep\PhpUtils\Classes\DtoHelper;
use Cogep\PhpUtils\Classes\DTOInterface;
use Cogep\PhpUtils\Inputs\Cli\AbstractCommand;
use Cogep\PhpUtils\Inputs\Cli\ConsoleCommandHelper;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AbstractCommandHelperTest extends MockeryTestCase
{
    private ConsoleCommandHelper $helper;

    private $dtoHelper;

    private $parameterBag;

    protected function setUp(): void
    {
        $this->dtoHelper = Mockery::mock(DtoHelper::class);
        $this->parameterBag = Mockery::mock(ParameterBagInterface::class);

        $this->helper = new ConsoleCommandHelper(
            Mockery::mock(SerializerInterface::class),
            Mockery::mock(\Psr\Log\LoggerInterface::class),
            $this->dtoHelper,
            $this->parameterBag
        );
    }

    public function testApplyCommandsMapping()
    {
        $dtoClass = get_class(new class('v') implements DTOInterface {
            public function __construct(
                public string $email
            ) {
            }
        });

        $command = Mockery::mock(AbstractCommand::class);
        $command->shouldReceive('getCommandsMapping')
            ->andReturn([
                'test' => 'task',
            ]);
        $command->shouldReceive('addOption')
            ->with('email', null, Mockery::any())->once();

        $this->dtoHelper->shouldReceive('getDtoClassFromMethod')
            ->andReturn($dtoClass);

        $this->helper->applyCommandsMapping($command);
    }

    public function testInteractivelyFillDto()
    {
        $dtoClass = get_class(new class('v') implements DTOInterface {
            public function __construct(
                public string $email
            ) {
            }
        });

        $input = Mockery::mock(InputInterface::class)->shouldIgnoreMissing();
        $input->shouldReceive('getOption')
            ->with('email')
            ->andReturn(null);
        $input->shouldReceive('setOption')
            ->with('email', Mockery::any())->once();

        $this->helper->interactivelyFillDto($input, new NullOutput(), $dtoClass);
        $this->assertTrue(true);
    }

    public function testGetMethodFromInput()
    {
        $command = Mockery::mock(AbstractCommand::class);
        $input = Mockery::mock(InputInterface::class);

        $command->shouldReceive('getCommandsMapping')
            ->andReturn([
                'run' => 'myMethod',
            ]);
        $input->shouldReceive('getArgument')
            ->with('action')
            ->andReturn('run');

        $this->assertEquals('myMethod', $this->helper->getMethodFromInput($command, $input));
    }

    public function testGetMethodFromInputThrowsException()
    {
        $command = Mockery::mock(AbstractCommand::class);
        $input = Mockery::mock(InputInterface::class);

        $command->shouldReceive('getCommandsMapping')
            ->andReturn([
                'a' => 'b',
            ]);
        $input->shouldReceive('getArgument')
            ->andReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->helper->getMethodFromInput($command, $input);
    }

    public function testSetupJsonLogging()
    {
        $command = Mockery::mock(AbstractCommand::class);
        $input = Mockery::mock(InputInterface::class);
        $command->shouldReceive('getName')
            ->andReturn('cmd');
        $input->shouldReceive('getArgument')
            ->andReturn('act');

        $this->parameterBag->shouldReceive('get')
            ->andReturn('/tmp');

        $realLogger = new Logger('test');
        $helper = new ConsoleCommandHelper(
            Mockery::mock(SerializerInterface::class),
            $realLogger,
            $this->dtoHelper,
            $this->parameterBag
        );

        $helper->setupJsonLogging($command, $input);
        $this->assertNotEmpty($realLogger->getHandlers());
    }

    public function testGetDtoFromMethod()
    {
        $command = Mockery::mock(AbstractCommand::class);
        $this->dtoHelper->shouldReceive('getDtoClassFromMethod')
            ->andReturn('FakeDto');

        $this->assertEquals('FakeDto', $this->helper->getDtoFromMethod($command, 'method'));
    }

    public function testFillDtoWithDatas()
    {
        $command = Mockery::mock(AbstractCommand::class);
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getOptions')
            ->andReturn([]);

        $dto = Mockery::mock(DTOInterface::class);
        $this->dtoHelper->shouldReceive('fillDtoFromMethodWithGivenData')
            ->andReturn($dto);

        $this->assertSame($dto, $this->helper->fillDtoWithDatas($command, 'method', $input));
    }
}
