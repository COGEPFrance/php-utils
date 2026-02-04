<?php

namespace Cogep\PhpUtils\Adapters\Input\Cli;

use Cogep\PhpUtils\Adapters\Input\Dtos\Responses\StandardResponseDto;
use Cogep\PhpUtils\Adapters\Input\ErrorCodeEnum;
use Cogep\PhpUtils\Adapters\Input\Exceptions\DomainException;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Serializer\SerializerInterface;

class ConsoleExceptionListener
{
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $input = $event->getInput();

        if (! $input->hasParameterOption('--json')) {
            return;
        }

        $exception = $event->getError();

        $errorCode = ($exception instanceof DomainException) ? $exception->getErrorCode() : null;

        $enumCode = $errorCode ?? ErrorCodeEnum::INTERNAL_ERROR;

        $dto = StandardResponseDto::error($enumCode, $exception->getMessage());

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
