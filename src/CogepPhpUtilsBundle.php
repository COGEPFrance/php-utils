<?php

namespace Cogep\PhpUtils;

use Cogep\PhpUtils\Command\BusCommand;
use Cogep\PhpUtils\Command\CommandRegistry;
use Cogep\PhpUtils\DependencyInjection\Compiler\BusCommandGeneratorPass;
use Cogep\PhpUtils\Inputs\Http\BusCommandRouteLoader;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands\RabbitMqCommandQueueHandler;
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
            ]);

        $queuesConfig = [
            'queue' => $_ENV['COMMAND_QUEUE'] ?? 'default_queue',
            'handler' => RabbitMqCommandQueueHandler::class,
        ];

        $locatorMapping = [
            $queuesConfig['queue'] => service($queuesConfig['handler']),
        ];

        $services->set('rabbitmq.handler_locator', ServiceLocator::class)
            ->args([$locatorMapping])
            ->tag('container.service_locator');

        $services->set(AMQPStreamConnection::class)
            ->arg('$host', '%env(RABBITMQ_CONNECTION)%')
            ->arg('$port', '%env(int:RABBITMQ_PORT)%')
            ->arg('$user', '%env(RABBITMQ_USER)%')
            ->arg('$password', '%env(RABBITMQ_PASSWORD)%');

        $services->set(RabbitMqWorker::class)
            ->arg('$dlq_queue', '%env(RABBITMQ_DLQ_QUEUE)%')
            ->arg('$handlerLocator', service('rabbitmq.handler_locator'));

        $services->set(RabbitMqConsumerCommand::class)
            ->arg('$queues', array_keys($locatorMapping));

        //        $services->set(BusCommandRouteLoader::class)
        //        ->tag('routing.loader');
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new BusCommandGeneratorPass());
    }
}
