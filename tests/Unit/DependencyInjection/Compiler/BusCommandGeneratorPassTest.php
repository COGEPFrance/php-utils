<?php

namespace Cogep\PhpUtils\Tests\Unit\DependencyInjection\Compiler;

use Cogep\PhpUtils\DependencyInjection\Compiler\BusCommandGeneratorPass;
use Cogep\PhpUtils\Helpers\EntityValidator;
use Cogep\PhpUtils\Inputs\Cli\ConsoleBusCommand;
use Cogep\PhpUtils\Inputs\Cli\ConsoleCommandHelper;
use Cogep\PhpUtils\Tests\Fixtures\DummyExposedCommand;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BusCommandGeneratorPassTest extends MockeryTestCase
{
    public function testProcessGeneratesDefinitions(): void
    {
        $container = new ContainerBuilder();

        $container->register(DummyExposedCommand::class)
            ->addTag('bus.command');

        $pass = new BusCommandGeneratorPass();
        $pass->process($container);

        $definitionId = 'console.bus.command.test-command';
        $this->assertTrue($container->hasDefinition($definitionId));

        $definition = $container->getDefinition($definitionId);

        $this->assertEquals(ConsoleBusCommand::class, $definition->getClass());

        $arguments = $definition->getArguments();

        $this->assertEquals('test-command', $arguments[0]); // Nom
        $this->assertEquals(DummyExposedCommand::class, $arguments[1]);    // DTO Class

        $this->assertInstanceOf(Reference::class, $arguments[2]);
        $this->assertEquals('serializer', (string) $arguments[2]);

        $this->assertInstanceOf(Reference::class, $arguments[5]);
        $this->assertEquals(ConsoleCommandHelper::class, (string) $arguments[5]);

        $this->assertInstanceOf(Reference::class, $arguments[6]);
        $this->assertEquals('messenger.default_bus', (string) $arguments[6]);

        $this->assertInstanceOf(Reference::class, $arguments[7]);
        $this->assertEquals(EntityValidator::class, (string) $arguments[7]);

        $this->assertTrue($definition->hasTag('console.command'));
        $tagAttributes = $definition->getTag('console.command');
        $this->assertEquals('test-command', $tagAttributes[0]['command']);
    }

    public function testProcessIgnoresNonExistentClasses(): void
    {
        $container = new ContainerBuilder();

        $container->register('NonExistentClass')
            ->addTag('bus.command');

        $pass = new BusCommandGeneratorPass();
        $pass->process($container);

        $busCommandDefinitions = array_filter(
            array_keys($container->getDefinitions()),
            fn ($id) => str_starts_with($id, 'console.bus.command.')
        );

        $this->assertEmpty(
            $busCommandDefinitions,
            'Aucune commande de bus ne devrait être générée pour une classe inexistante.'
        );
    }
}
