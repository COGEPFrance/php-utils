<?php

namespace Cogep\PhpUtils\Tests\Unit;

use Cogep\PhpUtils\CogepPhpUtilsBundle;
use Cogep\PhpUtils\Command\BusCommand;
use Cogep\PhpUtils\Command\CommandRegistry;
use Cogep\PhpUtils\DependencyInjection\Compiler\BusCommandGeneratorPass;
use Cogep\PhpUtils\Inputs\Rabbitmq\RabbitMqWorker;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class CogepPhpUtilsBundleTest extends MockeryTestCase
{
    protected function setUp(): void
    {
        $_ENV['APP_NAME'] = 'test-app';
        $_ENV['APP_VERSION'] = '1.0.0';
        $_ENV['APP_ENV'] = 'test';
        $_ENV['RABBITMQ_HOST'] = 'localhost';
        $_ENV['RABBITMQ_QUEUE_COMMANDS'] = 'test-queue';
        $_ENV['RABBITMQ_QUEUE_DLQ'] = 'test-dlq';
    }

    public function testBuildAddsCompilerPass(): void
    {
        $container = new ContainerBuilder();
        $bundle = new CogepPhpUtilsBundle();
        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()
            ->getPasses();
        $found = false;
        foreach ($passes as $pass) {
            if ($pass instanceof BusCommandGeneratorPass) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'BusCommandGeneratorPass devrait être ajouté au container.');
    }

    public function testLoadExtensionRegistersServices(): void
    {
        $container = new ContainerBuilder();
        $bundle = new CogepPhpUtilsBundle();

        $_ENV['COMMAND_QUEUE'] = 'test_queue';
        $_ENV['RABBITMQ_CONNECTION'] = 'localhost';
        $_ENV['RABBITMQ_PORT'] = '5672';
        $_ENV['RABBITMQ_USER'] = 'guest';
        $_ENV['RABBITMQ_PASSWORD'] = 'guest';
        $_ENV['RABBITMQ_DLQ_QUEUE'] = 'dlq';

        $test = [];
        $loader = new PhpFileLoader($container, new FileLocator());
        $configurator = new ContainerConfigurator($container, $loader, $test, 'test', 'test');

        $bundle->loadExtension([], $configurator, $container);

        $this->assertTrue($container->hasDefinition(CommandRegistry::class));
        $this->assertTrue($container->getDefinition(CommandRegistry::class)->isPublic());

        $this->assertTrue($container->hasDefinition(AMQPStreamConnection::class));
        $this->assertTrue($container->hasDefinition(RabbitMqWorker::class));

        $reflection = new \ReflectionClass($container);
        $property = $reflection->getProperty('autoconfiguredAttributes');
        $attributes = $property->getValue($container);

        $this->assertArrayHasKey(
            BusCommand::class,
            $attributes,
            "L'attribut BusCommand devrait être enregistré pour l'autoconfiguration."
        );
    }
}
