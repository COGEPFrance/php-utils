<?php

namespace Cogep\PhpUtils\Tests\Unit\Commands;

use Cogep\PhpUtils\Command\CommandRegistry;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use Cogep\PhpUtils\Tests\Classes\DummyDynamicDTO;
use Cogep\PhpUtils\Tests\Fixtures\DummyExposedCommand;
use Cogep\PhpUtils\Tests\Fixtures\DummyHiddenCommand;
use Psr\Log\LoggerInterface;

class CommandRegistryTest extends BaseMockeryTestCase
{
    public function testConstructorMapsCommandsWithAttributes(): void
    {
        $commands = [new DummyExposedCommand(), new DummyHiddenCommand(), new DummyDynamicDTO()];

        $registry = new CommandRegistry($commands, $this->createMock(LoggerInterface::class));

        $this->assertEquals(DummyExposedCommand::class, $registry->getDtoClass('test-command'));
        $this->assertEquals(DummyHiddenCommand::class, $registry->getDtoClass('secret-command'));
    }

    public function testGetDtoClassThrowsExceptionIfNotFound(): void
    {
        $registry = new CommandRegistry([], $this->createMock(LoggerInterface::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Commande [unknown.cmd] inconnue.');

        $registry->getDtoClass('unknown.cmd');
    }

    public function testAddCommandWithNonExistentClassDoesNothing(): void
    {
        $registry = new CommandRegistry([], $this->createMock(LoggerInterface::class));

        $registry->addCommand('NonExistent\\Class');

        $this->expectException(\InvalidArgumentException::class);
        $registry->getDtoClass('any');
    }

    public function testAddCommandExplicitly(): void
    {
        $registry = new CommandRegistry([], $this->createMock(LoggerInterface::class));

        $registry->addCommand(DummyExposedCommand::class);

        $this->assertEquals(DummyExposedCommand::class, $registry->getDtoClass('test-command'));
    }
}
