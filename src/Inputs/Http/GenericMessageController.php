<?php

namespace Cogep\PhpUtils\Inputs\Http;

use Cogep\PhpUtils\Helpers\EntityValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\SerializerInterface;

class GenericMessageController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private MessageBusInterface $bus,
        private EntityValidator $entityValidator
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {

        $message = $this->serializer->deserialize(
            $request->getContent(),
            $request->attributes->get('_message_class'),
            'json'
        );

        $this->entityValidator->validate($message);

        $envelope = $this->bus->dispatch($message);

        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse($result);
    }
}
