<?php

namespace Cogep\PhpUtils\Inputs\Rabbitmq;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:consume')]
class RabbitMqConsumerCommand extends Command
{
    /**
     * @param array<string> $queues
     */
    public function __construct(
        private readonly RabbitMqWorker $rabbitMqWorker,
        private readonly array $queues,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'queue_name',
            InputArgument::REQUIRED,
            'Le nom de la queue à choisir entre: ' . join(', ', $this->queues),
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (! $input->getArgument('queue_name')) {
            $io = new SymfonyStyle($input, $output);
            $choice = $io->choice('Quelle queue voulez-vous consommer ?', $this->queues);
            $input->setArgument('queue_name', $choice);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Démarrage du worker RabbitMQ...');
        $this->rabbitMqWorker->consume($input->getArgument('queue_name'));
        return Command::SUCCESS;
    }
}
