<?php

namespace Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands;

use Cogep\PhpUtils\Classes\Responses\StandardResponseDto;
use Cogep\PhpUtils\Command\CommandRegistry;
use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Cogep\PhpUtils\Helpers\EntityValidator;
use Cogep\PhpUtils\Inputs\Rabbitmq\Exceptions\MessageIgnoredException;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\RabbitMqQueueHandlerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class RabbitMqCommandQueueHandler implements RabbitMqQueueHandlerInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly DenormalizerInterface $denormalizer,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger,
        private readonly CommandRegistry $registry,
        private readonly EntityValidator $entityValidator,
    ) {
    }

    public function handle(array $data, AMQPMessage $originalMessage): void
    {
        try {
            [$command, $data] = $this->getCommandAndData($data);

            $dtoClass = $this->registry->getDtoClass($command);

            $dto = $this->denormalizer->denormalize($data, $dtoClass);

            $this->entityValidator->validate($dto);

            $envelope = $this->messageBus->dispatch($dto);

            $result = $envelope->last(HandledStamp::class)?->getResult();

            $this->replyIfRequested($originalMessage, $result);
        } catch (Throwable $exception) {
            $this->handleException($exception, $originalMessage);
        }
    }

    private function replyIfRequested(AMQPMessage $message, mixed $result): void
    {
        if (! $message->has('reply_to')) {
            return;
        }

        $response = ($result instanceof StandardResponseDto)
            ? $result
            : StandardResponseDto::success($result);

        $this->sendResponse($response, $message);
    }

    private function handleException(Throwable $e, AMQPMessage $message): void
    {
        if (! $message->has('reply_to')) {
            throw $e;
        }

        $response = match (true) {
            $e instanceof MessageIgnoredException => StandardResponseDto::error(
                ErrorCodeEnum::INVALID_INPUT,
                $e->getMessage()
            ),
            $e instanceof DomainException => StandardResponseDto::error($e->getErrorCode(), $e->getMessage()),
            default => StandardResponseDto::error(ErrorCodeEnum::INTERNAL_ERROR, 'Technical failure'),
        };

        $this->sendResponse($response, $message);

        if (isset($response->error->code) && $response->error->code === ErrorCodeEnum::INTERNAL_ERROR) {
            throw $e;
        }
    }

    /**
     * @param array<string,mixed> $data
     * @return array{
     *     0:string,
     *     1:array<string,mixed>
     * }
     */
    private function getCommandAndData(array $data): array
    {
        $command = $data['command'] ?? throw new MessageIgnoredException("Champ 'command' manquant");
        $data = $data['data'] ?? [];

        return [$command, $data];
    }

    private function sendResponse(StandardResponseDto $response, AMQPMessage $originalMessage): void
    {
        $channel = $originalMessage->getChannel();

        if (! $channel) {
            throw new \Exception('Missing channel in original message');
        }

        $jsonResponse = $this->serializer->serialize($response, 'json', [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
        ]);

        $responseMsg = new AMQPMessage(
            $jsonResponse,
            [
                'correlation_id' => $originalMessage->get('correlation_id'),
                'content_type' => 'application/json',
                'timestamp' => time(),
            ]
        );
        $this->logger->info("Envoi d'un message de réponse", [
            'body' => $jsonResponse,
            'queue' => $originalMessage->get('reply_to'),
        ]);
        $channel->basic_publish($responseMsg, '', $originalMessage->get('reply_to'));
    }
}
