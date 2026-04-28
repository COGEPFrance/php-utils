<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Cli;

use Cogep\PhpUtils\Inputs\Cli\ConsoleCommandHelper;
use Cogep\PhpUtils\Tests\Classes\DummyDynamicDTO;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConsoleCommandHelperTest extends TestCase
{
    private SerializerInterface $serializer;

    private LoggerInterface $logger;

    private ParameterBagInterface $parameterBag;

    private ConsoleCommandHelper $helper;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->helper = new ConsoleCommandHelper($this->serializer, $this->logger, $this->parameterBag);
    }

    public function testAddOptionsFromDto(): void
    {
        $command = new Command('test:cmd');

        $this->helper->addOptionsFromDto($command, DummyDynamicDTO::class);

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('name'));
        $this->assertTrue($definition->getOption('name')->isValueRequired());

        $this->assertTrue($definition->hasOption('age'));
        $this->assertTrue($definition->getOption('age')->isValueOptional());
    }

    public function testSetupJsonLogging(): void
    {
        $command = new Command('test:log');
        $input = $this->createMock(InputInterface::class);

        $logDir = sys_get_temp_dir() . '/php_utils_tests';
        $this->parameterBag->method('get')
            ->with('kernel.logs_dir')
            ->willReturn($logDir);

        $monolog = new Logger('test');
        $helper = new ConsoleCommandHelper($this->serializer, $monolog, $this->parameterBag);

        $helper->setupJsonLogging($command, $input);

        $handlers = $monolog->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);

        if (is_dir($logDir)) {
            array_map('unlink', glob("{$logDir}/*.*"));
            rmdir($logDir);
        }
    }

    public function testInteractivelyFillDto(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects($this->exactly(3))
            ->method('getOption')
            ->willReturnMap([['name', null], ['id', '25']]);

        $input->expects($this->atLeastOnce())
            ->method('setOption')
            ->with($this->logicalOr('name', 'age', 'id'), $this->anything());
        $this->helper->interactivelyFillDto($input, $output, DummyDynamicDTO::class);
    }
}
