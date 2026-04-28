<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Http;

use Cogep\PhpUtils\Inputs\Http\BusCommandRouteLoader;
use Cogep\PhpUtils\Inputs\Http\GenericMessageController;
use Cogep\PhpUtils\Tests\Fixtures\DummyExposedCommand;
use Cogep\PhpUtils\Tests\Fixtures\DummyHiddenCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;

class BusCommandRouteLoaderTest extends TestCase
{
    public function testSupportsReturnsTrueForCorrectType(): void
    {
        $loader = new BusCommandRouteLoader([]);
        $this->assertTrue($loader->supports(null, BusCommandRouteLoader::BUS_COMMAND_API));
        $this->assertFalse($loader->supports(null, 'other_type'));
    }

    public function testLoadAddsRoutesOnlyForExposedCommands(): void
    {
        $exposedCommand = new DummyExposedCommand();
        $hiddenCommand = new DummyHiddenCommand();
        $noAttributeCommand = new class() {
        };

        $commands = [$exposedCommand, $hiddenCommand, $noAttributeCommand];

        $loader = new BusCommandRouteLoader($commands);
        $routes = $loader->load(null, BusCommandRouteLoader::BUS_COMMAND_API);

        $this->assertInstanceOf(RouteCollection::class, $routes);

        $this->assertCount(1, $routes);

        $route = $routes->get('api_bus_test-command');
        $this->assertNotNull($route);
        $this->assertEquals('/test/command', $route->getPath());
        $this->assertEquals(['POST'], $route->getMethods());

        $this->assertEquals(GenericMessageController::class, $route->getDefault('_controller'));
        $this->assertEquals(get_class($exposedCommand), $route->getDefault('_message_class'));
        $this->assertEquals('POST', $route->getDefault('_method'));
    }
}
