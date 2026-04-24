<?php

namespace Cogep\PhpUtils;

use Cogep\PhpUtils\Command\BusCommand;
use Cogep\PhpUtils\Command\CommandRegistry;
use Cogep\PhpUtils\Config\Settings;
use Cogep\PhpUtils\DependencyInjection\Compiler\BusCommandGeneratorPass;
use Cogep\PhpUtils\Inputs\Rabbitmq\RabbitMqConsumerCommand;
use Cogep\PhpUtils\Inputs\Rabbitmq\RabbitMqWorker;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CogepPhpUtilsBundle extends AbstractBundle
{
    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->defaults()
            ->autowire()
            ->autoconfigure();

        $builder->registerAttributeForAutoconfiguration(
            BusCommand::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('bus.command');
                $definition->setPublic(true);
            }
        );

        $services->set(CommandRegistry::class)
            ->arg('$commands', tagged_iterator('bus.command'))
            ->public();

        $services->load('Cogep\\PhpUtils\\', __DIR__ . '/')
            ->exclude([
                __DIR__ . '/DependencyInjection',
                __DIR__ . '/Tests',
                __DIR__ . '/CogepPhpUtilsBundle.php',
                __DIR__ . '/Inputs/Cli/ConsoleBusCommand.php',
                __DIR__ . '/Command/CommandRegistry.php',
                __DIR__ . '/Config/Settings.php',
            ]);

        $settings = Settings::fromEnv();
        $services->set(Settings::class, Settings::class)
            ->factory([Settings::class, 'fromEnv']);

        $mapping = array_map(function ($handlerClass) {
            return service($handlerClass);
        }, $settings->getQueueMapping());

        $services->set('rabbitmq.handler_locator', ServiceLocator::class)
            ->args([$mapping])
            ->tag('container.service_locator');

        $services->set(AMQPStreamConnection::class)
            ->args([$settings->rabbitHost, $settings->rabbitPort, $settings->rabbitUser, $settings->rabbitPass]);

        $services->set(RabbitMqWorker::class)
            ->arg('$dlq_queue', $settings->rabbitQueueDlq)
            ->arg('$handlerLocator', service('rabbitmq.handler_locator'));

        $services->set(RabbitMqConsumerCommand::class)
            ->arg('$queues', array_keys($mapping));
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new BusCommandGeneratorPass());
    }
}
