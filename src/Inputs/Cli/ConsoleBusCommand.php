<?php

namespace Cogep\PhpUtils\Inputs\Cli;

use Cogep\PhpUtils\Classes\DTOInterface;
use Cogep\PhpUtils\Classes\Responses\StandardResponseDto;
use Cogep\PhpUtils\Helpers\EntityValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConsoleBusCommand extends Command
{
    /**
     * @param class-string<DTOInterface> $dtoClass
     */
    public function __construct(
        string $name,
        protected readonly string $dtoClass,
        protected readonly SerializerInterface $serializer,
        protected readonly DenormalizerInterface $denormalizer,
        protected LoggerInterface $logger,
        protected readonly ConsoleCommandHelper $helper,
        protected readonly MessageBusInterface $bus,
        protected readonly EntityValidator $entityValidator,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Output in JSON format');
        $this->helper->addOptionsFromDto($this, $this->dtoClass);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ($input->hasParameterOption('--json')) {
            $this->helper->setupJsonLogging($this, $input);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('json')) {
            $this->helper->setupJsonLogging($this, $input);
        }

        if (! $input->getOption('json')) {
            $this->helper->interactivelyFillDto($input, $output, $this->dtoClass);
        }

        $data = $input->getOptions();

        $dto = $this->denormalizer->denormalize($data, $this->dtoClass);

        $this->entityValidator->validate($dto);

        $envelope = $this->bus->dispatch($dto);

        $result = $envelope->last(HandledStamp::class)?->getResult();

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
