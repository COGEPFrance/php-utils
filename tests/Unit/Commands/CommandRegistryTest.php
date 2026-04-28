<?php

namespace Cogep\PhpUtils\Tests\Unit\Command;

use Cogep\PhpUtils\Command\CommandRegistry;
use Cogep\PhpUtils\Tests\Classes\DummyDynamicDTO;
use Cogep\PhpUtils\Tests\Fixtures\DummyExposedCommand;
use Cogep\PhpUtils\Tests\Fixtures\DummyHiddenCommand;
use PHPUnit\Framework\TestCase;

class CommandRegistryTest extends TestCase
{
    public function testConstructorMapsCommandsWithAttributes(): void
    {
        $commands = [new DummyExposedCommand(), new DummyHiddenCommand(), new DummyDynamicDTO()];

        $registry = new CommandRegistry($commands);

        $this->assertEquals(DummyExposedCommand::class, $registry->getDtoClass('test-command'));
        $this->assertEquals(DummyHiddenCommand::class, $registry->getDtoClass('secret-command'));
    }

    public function testGetDtoClassThrowsExceptionIfNotFound(): void
    {
        $registry = new CommandRegistry([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Commande [unknown.cmd] inconnue.');

        $registry->getDtoClass('unknown.cmd');
    }

    public function testAddCommandWithNonExistentClassDoesNothing(): void
    {
        $registry = new CommandRegistry([]);

        $registry->addCommand('NonExistent\\Class');

        $this->expectException(\InvalidArgumentException::class);
        $registry->getDtoClass('any');
    }

    public function testAddCommandExplicitly(): void
    {
        $registry = new CommandRegistry([]);

        $registry->addCommand(DummyExposedCommand::class);

        $this->assertEquals(DummyExposedCommand::class, $registry->getDtoClass('test-command'));
    }
}
