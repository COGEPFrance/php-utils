<?php

namespace Cogep\PhpUtils;

use Cogep\PhpUtils\Adapters\Input\RabbitMq\RabbitMqConsumerCommand;
use Cogep\PhpUtils\Adapters\Input\RabbitMq\RabbitMqWorker;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CogepPhpUtilsBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->arrayNode('queues')
            ->ignoreExtraKeys()
            ->defaultValue([])
            ->prototype('scalar')
            ->end();
    }

    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $queuesConfig = $config['queues'] ?? [];

        $handlerServices = [];
        foreach ($queuesConfig as $name => $class) {
            $handlerServices[$name] = new Reference($class);
        }

        $builder->register('rabbitmq.handler_locator', ServiceLocator::class)
            ->addArgument($handlerServices)
            ->addTag('container.service_locator');

        $builder->getDefinition(RabbitMqWorker::class)
            ->setArgument('$handlerLocator', new Reference('rabbitmq.handler_locator'));

        $builder->getDefinition(RabbitMqConsumerCommand::class)
            ->setArgument('$queues', array_keys($queuesConfig));
    }
}
