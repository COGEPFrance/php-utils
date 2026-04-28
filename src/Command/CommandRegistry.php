<?php

namespace Cogep\PhpUtils\Command;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class CommandRegistry
{
    /**
     * @var array<string, string>
     */
    private array $mapping = [];

    /**
     * @param iterable<object> $commands
     */
    public function __construct(#[AutowireIterator('bus.command')] iterable $commands)
    {
        foreach ($commands as $command) {
            $this->addCommand($command::class);
        }
    }

    public function addCommand(string $className): void
    {
        if (! class_exists($className)) {
            return;
        }

        $reflection = new \ReflectionClass($className);
        $attrs = $reflection->getAttributes(BusCommand::class);

        if (isset($attrs[0])) {
            $instance = $attrs[0]->newInstance();
            $this->mapping[$instance->name] = $className;
        }
    }

    public function getDtoClass(string $name): string
    {
        return $this->mapping[$name] ?? throw new \InvalidArgumentException("Commande [{$name}] inconnue.");
    }
}
