<?php

namespace Cogep\PhpUtils\DependencyInjection\Compiler;

use Cogep\PhpUtils\Command\BusCommand;
use Cogep\PhpUtils\Helpers\EntityValidator;
use Cogep\PhpUtils\Inputs\Cli\ConsoleBusCommand;
use Cogep\PhpUtils\Inputs\Cli\ConsoleCommandHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BusCommandGeneratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('bus.command') as $class => $tags) {
            if (! class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $attr = $reflection->getAttributes(BusCommand::class)[0]->newInstance();

            $commandName = $attr->name;

            $definition = new Definition(ConsoleBusCommand::class);
            $definition->setArguments([
                $commandName,
                $class,
                new Reference('serializer'),
                new Reference('serializer'),
                new Reference('logger'),
                new Reference(ConsoleCommandHelper::class),
                new Reference('messenger.default_bus'),
                new Reference(EntityValidator::class),
            ]);

            $definition->addTag('console.command', [
                'command' => $commandName,
            ]);

            $container->setDefinition('console.bus.command.' . str_replace(':', '_', $commandName), $definition);
        }
    }
}
