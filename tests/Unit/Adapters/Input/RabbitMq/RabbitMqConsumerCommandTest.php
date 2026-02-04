<?php

namespace Unit\Adapters\Input\RabbitMq;

use Cogep\PhpUtils\Adapters\Input\RabbitMq\RabbitMqConsumerCommand;
use Cogep\PhpUtils\Adapters\Input\RabbitMq\RabbitMqWorker;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RabbitMqConsumerCommandTest extends TestCase
{
    private $worker;

    private array $queues = ['queue_1', 'queue_2'];

    protected function setUp(): void
    {
        $this->worker = Mockery::mock(RabbitMqWorker::class);
    }

    public function testExecuteWithArgument()
    {
        $command = new RabbitMqConsumerCommand($this->worker, $this->queues);
        $commandTester = new CommandTester($command);

        $this->worker->shouldReceive('consume')
            ->once()
            ->with('queue_1');

        $commandTester->execute([
            'queue_name' => 'queue_1',
        ]);

        $this->assertStringContainsString('Démarrage du worker RabbitMQ...', $commandTester->getDisplay());
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testInteractProvidesChoiceIfArgumentMissing()
    {
        $command = new RabbitMqConsumerCommand($this->worker, $this->queues);
        $commandTester = new CommandTester($command);

        $this->worker->shouldReceive('consume')
            ->once()
            ->with('queue_2');

        $commandTester->setInputs(['queue_2']);
        $commandTester->execute([], [
            'interactive' => true,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Quelle queue voulez-vous consommer ?', $output);
        $this->assertStringContainsString('Démarrage du worker RabbitMQ...', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
