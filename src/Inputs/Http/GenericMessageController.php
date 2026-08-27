<?php

namespace Cogep\PhpUtils\Inputs\Http;

use Cogep\PhpUtils\Classes\FilterDataDtoInterface;
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
        $messageClass = $request->attributes->get('_message_class');

        if (! is_string($messageClass) || ! class_exists($messageClass)) {
            throw new \InvalidArgumentException('Missing message class.');
        }

        $payload = $this->getPayload($request, $messageClass);

        $message = $this->serializer->deserialize(
            json_encode($payload, JSON_THROW_ON_ERROR),
            $messageClass,
            'json'
        );

        $this->entityValidator->validate($message);

        $envelope = $this->bus->dispatch($message);

        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse($result);
    }

    /**
     * @param class-string $messageClass
     *
     * @return array<string, mixed>
     */
    private function getPayload(Request $request, string $messageClass): array
    {
        $queryPayload = $request->query->all();
        $bodyPayload = $this->getBodyPayload($request);

        if (is_subclass_of($messageClass, FilterDataDtoInterface::class)) {
            $routeParams = array_filter(
                $request->attributes->all(),
                static fn (string $key): bool => ! str_starts_with($key, '_'),
                ARRAY_FILTER_USE_KEY
            );

            return [
                'filter' => array_replace($queryPayload, $routeParams),
                'data' => $bodyPayload,
            ];
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $queryPayload;
        }

        return array_replace($queryPayload, $bodyPayload);
    }

    /**
     * @return array<string, mixed>
     */
    private function getBodyPayload(Request $request): array
    {
        $content = trim($request->getContent());

        if ($content === '') {
            return [];
        }

        $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw new \InvalidArgumentException('JSON body must be an object.');
        }

        return $payload;
    }
}
