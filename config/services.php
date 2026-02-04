<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Cogep\PhpUtils\Adapters\Input\Cli\ConsoleExceptionListener;
use Cogep\PhpUtils\Adapters\Input\Http\ApiExceptionListener;
use Cogep\PhpUtils\Adapters\Input\RabbitMq\QueueHandlers\Commands\RabbitMqCommandQueueHandler;
use Cogep\PhpUtils\Adapters\Input\RabbitMq\RabbitMqConsumerCommand;
use Cogep\PhpUtils\Adapters\Input\RabbitMq\RabbitMqWorker;
use Cogep\PhpUtils\DependencyInjection\ServiceLocatorFactory;
use Cogep\PhpUtils\Logs\UrlTruncatorProcessor;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\DependencyInjection\ServiceLocator;

return function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->load('Cogep\\PhpUtils\\', '../src/*')
        ->exclude('./{Tests}')
        ->autowire()
        ->autoconfigure();


    // --- CONFIGURATION LOGS --
    $services->set(UrlTruncatorProcessor::class)
        ->tag('monolog.processor');

    // -- CONFIGURATION RABBITMQ  --

    $services->set(AMQPStreamConnection::class)
        ->arg('$host', '%env(RABBITMQ_CONNECTION)%')
        ->arg('$port', '%env(RABBITMQ_PORT)%')
        ->arg('$user', '%env(RABBITMQ_USER)%')
        ->arg('$password', '%env(RABBITMQ_PASSWORD)%');

    $services->set(RabbitMqWorker::class)
        ->arg('$dlq_queue', '%env(RABBITMQ_DLQ_QUEUE)%');

//    $services->set(RabbitMqConsumerCommand::class);

    // Queues Handlers
    $services->set(RabbitMqCommandQueueHandler::class)
        ->arg('$handlers', tagged_iterator('command_message_handler'));

    // --- CONFIGURATION API ---
    $services->set(ApiExceptionListener::class)
        ->tag('kernel.event_listener', [
            'event' => 'kernel.exception',
            'method' => 'onKernelException',
            'priority' => 10
        ]);

    // --- CONFIGURATION CLI ---
    $services->set(ConsoleExceptionListener::class)
        ->arg('$serializer', service('serializer'))
        ->tag('kernel.event_listener', [
            'event' => 'console.error',
            'method' => 'onConsoleError',
            'priority' => 10,
        ])
        ->tag('kernel.event_listener', [
            'event' => 'console.terminate',
            'method' => 'onConsoleTerminate',
            'priority' => 10,
        ]);
};
