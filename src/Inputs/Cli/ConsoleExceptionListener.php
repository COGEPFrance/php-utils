<?php

namespace Cogep\PhpUtils\Inputs\Cli;

use Cogep\PhpUtils\Classes\Responses\StandardResponseDto;
use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsEventListener(event: 'console.error', method: 'onConsoleError')]
#[AsEventListener(event: 'console.terminate', method: 'onConsoleTerminate')]
class ConsoleExceptionListener
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $exception = $event->getError();

        if ($exception instanceof HandlerFailedException && $exception->getPrevious()) {
            $exception = $exception->getPrevious();
        }

        $errorCode = ($exception instanceof DomainException)
            ? $exception->getErrorCode()
            : ErrorCodeEnum::INTERNAL_ERROR;

        $this->logger->error(
            $exception->getMessage(),
            [
                'exception' => $exception,
                'trace' => $exception->getTraceAsString(),
            ]
        );

        if (! $event->getInput()->hasParameterOption('--json')) {
            return;
        }

        $dto = StandardResponseDto::error($errorCode, $exception->getMessage());

        $json = $this->serializer->serialize($dto, 'json', [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ]);
        fwrite(STDOUT, $json . PHP_EOL);
        $event->setExitCode(1);
        $event->stopPropagation();
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if ($event->getInput()->hasParameterOption('--json')) {
            $event->stopPropagation();
        }
    }
}
