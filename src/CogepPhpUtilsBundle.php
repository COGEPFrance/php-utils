<?php

namespace Cogep\PhpUtils;

use Cogep\PhpUtils\Command\BusCommand;
use Cogep\PhpUtils\Command\CommandRegistry;
use Cogep\PhpUtils\Config\Settings;
use Cogep\PhpUtils\DependencyInjection\Compiler\BusCommandGeneratorPass;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\RabbitMqQueueHandlerInterface;
use Cogep\PhpUtils\Inputs\Rabbitmq\RabbitMqWorker;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
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

        $builder->registerForAutoconfiguration(RabbitMqQueueHandlerInterface::class)
            ->addTag('rabbitmq.handler')
            ->setPublic(true);

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

        $services->set(Settings::class, Settings::class)
            ->factory([Settings::class, 'fromEnv']);

        $services->set(AMQPStreamConnection::class)
            ->factory([self::class, 'createConnection']);

        $services->set(RabbitMqWorker::class)
            ->autowire()
            ->arg('$container', service('service_container'))
            ->public();
    }

    public static function createConnection(Settings $settings): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            $settings->rabbitHost,
            $settings->rabbitPort,
            $settings->rabbitUser,
            $settings->rabbitPass
        );
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new BusCommandGeneratorPass());
    }
}
