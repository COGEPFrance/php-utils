<?php

namespace Unit\Adapters\Input\Cli;

use Cogep\PhpUtils\Adapters\Input\Cli\AbstractCommand;
use Cogep\PhpUtils\Adapters\Input\Cli\AbstractCommandHelper;
use Cogep\PhpUtils\Adapters\Input\Dtos\Responses\StandardResponseDto;
use Cogep\PhpUtils\Adapters\Input\ErrorCodeEnum;
use Cogep\PhpUtils\Classes\Dtos\DTOInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Serializer\SerializerInterface;

class AbstractCommandTest extends MockeryTestCase
{
    public function testExecuteSuccess()
    {
        $tester = $this->createCommandTester(StandardResponseDto::success(''));
        $tester->execute([
            'action' => 'test',
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('Operation successful', $tester->getDisplay());
    }

    public function testExecuteError()
    {
        $tester = $this->createCommandTester(StandardResponseDto::error(ErrorCodeEnum::INTERNAL_ERROR, 'Oups'));
        $tester->execute([
            'action' => 'test',
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
    }

    public function testExecuteErrorUnknown()
    {
        $res = new StandardResponseDto('error', null, null, null);

        $tester = $this->createCommandTester($res);
        $tester->execute([
            'action' => 'test',
        ]);
        $this->assertStringContainsString('An unknown error occurred', $tester->getDisplay());
    }

    public function testExecuteJson()
    {
        $tester = $this->createCommandTester(StandardResponseDto::success(''), true);

        ob_start();
        $tester->execute([
            'action' => 'test',
            '--json' => true,
        ]);
        $output = ob_get_clean();

        $this->assertJson($output);
    }

    /**
     * Cette fonction crée une nouvelle commande et son testeur à chaque appel
     */
    private function createCommandTester(StandardResponseDto $result, bool $isJson = false): CommandTester
    {
        $serializer = Mockery::mock(SerializerInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $helper = Mockery::mock(AbstractCommandHelper::class);

        $serializer->shouldReceive('serialize')
            ->andReturn(json_encode($result));

        $helper->shouldReceive('applyCommandsMapping')
            ->once();
        $helper->shouldReceive('getMethodFromInput')
            ->andReturn('maMethode');
        $helper->shouldReceive('getDtoFromMethod')
            ->andReturn('FakeDto');
        $helper->shouldReceive('fillDtoWithDatas')
            ->andReturn(Mockery::mock(DTOInterface::class));

        if (! $isJson) {
            $helper->shouldReceive('interactivelyFillDto')
                ->once();
        } else {
            $helper->shouldReceive('setupJsonLogging')
                ->once();
        }

        $command = new \FakeCommand($serializer, $logger, $helper);
        $command->resultatVoulu = $result;

        return new CommandTester($command);
    }
}

/**
 * FIXTURE (en dehors de la classe de test ou en bas du fichier)
 */
class FakeCommand extends AbstractCommand
{
    public $resultatVoulu;

    public function getCommandsMapping(): array
    {
        return [
            'test' => 'maMethode',
        ];
    }

    public function maMethode($dto)
    {
        return $this->resultatVoulu;
    }

    protected function terminate(int $code): void
    { /* Bloque le exit */
    }
}
