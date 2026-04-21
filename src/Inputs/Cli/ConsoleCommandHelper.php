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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConsoleCommandHelper
{
    public function __construct(
        protected readonly SerializerInterface $serializer,
        protected LoggerInterface $logger,
        protected readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function setupJsonLogging(Command $command, InputInterface $input): void
    {
        if (! $this->logger instanceof Logger) {
            return;
        }

        foreach ($this->logger->getHandlers() as $handler) {
            $this->logger->popHandler();
        }

        $logDir = $this->parameterBag->get('kernel.logs_dir');

        if (! is_string($logDir)) {
            throw new \RuntimeException('Le paramètre kernel.logs_dir doit être une chaîne de caractères.');
        }

        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logPath = sprintf(
            '%s/%s_%s.log',
            $logDir,
            str_replace(':', '_', (string) $command->getName()),
            date('Y-m-d_H-i-s')
        );

        $jsonHandler = new StreamHandler($logPath);
        $jsonHandler->setFormatter(new LoggerFormator());
        $this->logger->pushHandler($jsonHandler);

        $this->logger->debug('écriture des logs dans un fichier.');
    }

    /**
     * @param class-string<DTOInterface> $dtoClass
     */
    public function interactivelyFillDto(InputInterface $input, OutputInterface $output, string $dtoClass): void
    {
        $io = new SymfonyStyle($input, $output);
        $reflection = new ReflectionClass($dtoClass);
        $params = $reflection->getConstructor()?->getParameters() ?? [];

        foreach ($params as $param) {
            $name = $param->getName();

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
            $this->addOptionFromReflection($command, $property->getName(), true);
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
