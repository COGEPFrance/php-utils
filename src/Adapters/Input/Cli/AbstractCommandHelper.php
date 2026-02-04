<?php

namespace Cogep\PhpUtils\Adapters\Input\Cli;

use Cogep\PhpUtils\Classes\Dtos\DtoHelper;
use Cogep\PhpUtils\Classes\Dtos\DTOInterface;
use Cogep\PhpUtils\Logs\LoggerFormator;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AbstractCommandHelper
{
    public function __construct(
        protected readonly SerializerInterface $serializer,
        protected LoggerInterface $logger,
        private readonly DtoHelper $dtoHelper,
        protected readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function setupJsonLogging(AbstractCommand $command, InputInterface $input): void
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
            '%s/%s_%s_%s.log',
            $logDir,
            str_replace(':', '_', (string) $command->getName()),
            $input->getArgument('action'),
            date('Y-m-d_H-i-s')
        );

        $jsonHandler = new StreamHandler($logPath);

        $jsonHandler->setFormatter(new LoggerFormator());

        $this->logger->pushHandler($jsonHandler);

        $this->logger->debug('écriture des logs dans un fichier.');

    }

    public function applyCommandsMapping(AbstractCommand $command): void
    {
        foreach ($command->getCommandsMapping() as $action => $method) {
            try {
                $dtoClass = $this->dtoHelper->getDtoClassFromMethod($command, $method);
                $this->addOptionsFromDto($command, $dtoClass);
            } catch (\Exception $e) {
                $this->logger->error("Erreur mapping DTO pour {$method} : " . $e->getMessage());
            }
        }
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

    public function getMethodFromInput(AbstractCommand $command, InputInterface $input): string
    {
        $action = $input->getArgument('action');

        if (! $action) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Missing action. Available actions: %s',
                    implode(', ', array_keys($command->getCommandsMapping()))
                )
            );
        }

        $mapping = $command->getCommandsMapping();

        if (! isset($mapping[$action])) {
            throw new \InvalidArgumentException("Action [{$action}] non reconnue.");
        }

        return $mapping[$action];
    }

    /**
     * @return class-string<DTOInterface>
     */
    public function getDtoFromMethod(AbstractCommand $command, string $method): string
    {
        return $this->dtoHelper->getDtoClassFromMethod($command, $method);
    }

    public function fillDtoWithDatas(AbstractCommand $command, string $method, InputInterface $input): DTOInterface
    {
        return $this->dtoHelper->fillDtoFromMethodWithGivenData($command, $method, $input->getOptions());
    }

    /**
     * @param class-string<DTOInterface> $dtoClass
     */
    protected function addOptionsFromDto(AbstractCommand $command, string $dtoClass): void
    {
        $reflection = new \ReflectionClass($dtoClass);
        $properties = $reflection->getConstructor()?->getParameters() ?? [];

        foreach ($properties as $param) {
            $command->addOption(
                $param->getName(),
                null,
                $param->isOptional() ? InputOption::VALUE_OPTIONAL : InputOption::VALUE_REQUIRED,
            );
        }
    }
}
