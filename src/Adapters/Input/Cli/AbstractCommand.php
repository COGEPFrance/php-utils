<?php

namespace Cogep\PhpUtils\Adapters\Input\Cli;

use Cogep\PhpUtils\Adapters\Input\Dtos\Responses\StandardResponseDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractCommand extends Command
{
    public function __construct(
        protected readonly SerializerInterface $serializer,
        protected LoggerInterface $logger,
        private readonly AbstractCommandHelper $helper,
    ) {
        parent::__construct();
        foreach ($this->getCommandsMapping() as $action => $method) {
            if (! method_exists($this, $method)) {
                throw new \LogicException(
                    sprintf(
                        "L'action CLI %s pointe vers la méthode %s() qui n'existe pas dans la classe %s.",
                        $action,
                        $method,
                        static::class
                    )
                );
            }
        }
    }

    /**
     * @return array<string,string>
     */
    abstract public function getCommandsMapping(): array;

    protected function configure(): void
    {
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Output in JSON format')
            ->addArgument('action', InputArgument::OPTIONAL, 'L\'action à exécuter');
        $this->helper->applyCommandsMapping($this);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ($input->hasParameterOption('--json')) {
            $this->helper->setupJsonLogging($this, $input);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $method = $this->helper->getMethodFromInput($this, $input);
        $dtoClass = $this->helper->getDtoFromMethod($this, $method);

        if (! $input->getOption('json')) {
            $this->helper->interactivelyFillDto($input, $output, $dtoClass);
        }

        $dto = $this->helper->fillDtoWithDatas($this, $method, $input);
        $result = $this->{$method}($dto);

        return $this->render($output, $result, $input->getOption('json'));

    }

    protected function render(OutputInterface $output, StandardResponseDto $dto, bool $isJson): int
    {
        $isSuccess = $dto->status === 'success';
        $exitCode = $isSuccess ? Command::SUCCESS : Command::FAILURE;

        $json = $this->serializer->serialize($dto, 'json', [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        ]);

        if ($isJson) {
            return $this->renderJson($json, $exitCode);
        }

        $this->renderVisual($output, $dto, $json, $isSuccess);

        return $exitCode;
    }

    protected function terminate(int $code): void
    {
        exit($code);
    }

    private function renderJson(string $json, int $exitCode): int
    {
        if (ob_get_length()) {
            ob_clean();
        }

        echo $json . PHP_EOL;
        $this->terminate($exitCode);

        return $exitCode;
    }

    private function renderVisual(
        OutputInterface $output,
        StandardResponseDto $dto,
        string $json,
        bool $isSuccess
    ): void {
        $io = new SymfonyStyle(new ArrayInput([]), $output);

        if ($isSuccess) {
            $io->success('Operation successful');
            $io->text($json);
            return;
        }
        if (isset($dto->error)) {
            $io->error($dto->error->message);
            return;
        }

        $io->error('An unknown error occurred');
    }
}
