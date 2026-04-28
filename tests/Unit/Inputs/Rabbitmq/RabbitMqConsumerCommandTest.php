<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Rabbitmq;

use Cogep\PhpUtils\Inputs\Rabbitmq\RabbitMqConsumerCommand;
use Cogep\PhpUtils\Inputs\Rabbitmq\RabbitMqWorker;
use Cogep\PhpUtils\Tests\Fixtures\TestConfig;
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
        $command = new RabbitMqConsumerCommand($this->worker, new TestConfig());
        $commandTester = new CommandTester($command);

        $this->worker->shouldReceive('consume')
            ->once()
            ->with('queue_1');

        $commandTester->execute([
            'queue_name' => 'queue_1',
        ]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Démarrage du worker sur la queue queue_1', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testInteractProvidesChoiceIfArgumentMissing()
    {
        $command = new RabbitMqConsumerCommand($this->worker, new TestConfig());
        $commandTester = new CommandTester($command);

        $this->worker->shouldReceive('consume')
            ->once()
            ->with('queue_1');

        $commandTester->setInputs(['queue_1']);
        $commandTester->execute([], [
            'interactive' => true,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Quelle queue voulez-vous consommer ?', $output);
        $this->assertStringContainsString('Démarrage du worker sur la queue queue_1', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
