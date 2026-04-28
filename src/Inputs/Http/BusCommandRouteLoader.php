<?php

namespace Cogep\PhpUtils\Inputs\Http;

use Cogep\PhpUtils\Command\BusCommand;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

#[AutoconfigureTag('routing.loader')]
class BusCommandRouteLoader extends Loader
{
    public const string BUS_COMMAND_API = 'bus_commands_api';

    /**
     * @param iterable<object> $commands
     */
    public function __construct(
        #[AutowireIterator('bus.command')]
        private readonly iterable $commands = []
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        foreach ($this->commands as $command) {
            $reflection = new \ReflectionClass($command);
            $attribute = $reflection->getAttributes(BusCommand::class)[0] ?? null;

            if (! $attribute) {
                continue;
            }

            $config = $attribute->newInstance();
            if (! $config->exposeApi) {
                continue;
            }

            $path = '/' . str_replace('-', '/', $config->name);

            $routes->add(
                'api_bus_' . str_replace(':', '_', $config->name),
                new Route(
                    $path,
                    [
                        '_controller' => GenericMessageController::class,
                        '_message_class' => $reflection->getName(),
                        '_method' => $config->method,
                    ],
                    [],
                    [],
                    '',
                    [],
                    [$config->method]
                )
            );
        }

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === self::BUS_COMMAND_API;
    }
}
