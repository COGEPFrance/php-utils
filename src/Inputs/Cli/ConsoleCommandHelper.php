<?php

namespace Cogep\PhpUtils\Inputs\Cli;

use Cogep\PhpUtils\Classes\DTOInterface;
use Cogep\PhpUtils\Logs\LoggerFormator;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

class ConsoleCommandHelper
{
    public function __construct(
        protected readonly SerializerInterface $serializer,
        protected LoggerInterface $logger,
    ) {
    }

    public function setupConsoleLogging(): void
    {
        if (! ($this->logger instanceof Logger)) {
            return;
        }

        $consoleHandler = new StreamHandler('php://stdout');
        $consoleHandler->setFormatter(new LoggerFormator());
        $this->logger->pushHandler($consoleHandler);
    }

    /**
     * @param class-string<DTOInterface> $dtoClass
     */
    public function interactivelyFillDto(InputInterface $input, OutputInterface $output, string $dtoClass): void
    {
        $io = new SymfonyStyle($input, $output);
        $reflection = new ReflectionClass($dtoClass);

        $optionNames = [];

        $params = $reflection->getConstructor()?->getParameters() ?? [];
        foreach ($params as $param) {
            $optionNames[] = $param->getName();
        }

        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $optionNames[] = $property->getName();
        }

        foreach (array_unique($optionNames) as $name) {
            if (! $input->getOption($name)) {
                $value = $io->ask(sprintf('Valeur pour <info>%s</info> ?', $name));
                $input->setOption($name, $value);
            }
        }
    }

    /**
     * @param class-string<DTOInterface> $dtoClass
     */
    public function addOptionsFromDto(Command $command, string $dtoClass): void
    {
        $reflection = new \ReflectionClass($dtoClass);

        $params = $reflection->getConstructor()?->getParameters() ?? [];
        foreach ($params as $param) {
            $this->addOptionFromReflection($command, $param->getName(), $param->isOptional());
        }

        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            if ($command->getDefinition()->hasOption($property->getName())) {
                continue;
            }
            $isOptional = $property->hasDefaultValue() || $property->getType()?->allowsNull();
            $this->addOptionFromReflection($command, $property->getName(), $isOptional);
        }
    }

    private function addOptionFromReflection(Command $command, string $name, bool $isOptional): void
    {
        $command->addOption(
            $name,
            null,
            $isOptional ? InputOption::VALUE_OPTIONAL : InputOption::VALUE_REQUIRED,
            'Auto-generated option'
        );
    }
}
