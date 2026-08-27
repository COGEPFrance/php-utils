<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Http;

use Cogep\PhpUtils\Helpers\EntityValidator;
use Cogep\PhpUtils\Inputs\Http\GenericMessageController;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use Cogep\PhpUtils\Tests\Fixtures\FilterData\FilterDataCommandFixture;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\SerializerInterface;

class GenericMessageControllerTest extends BaseMockeryTestCase
{
    private $serializer;

    private $bus;

    private $validator;

    private $controller;

    protected function setUp(): void
    {
        $this->serializer = \Mockery::mock(SerializerInterface::class);
        $this->bus = \Mockery::mock(MessageBusInterface::class);
        $this->validator = \Mockery::mock(EntityValidator::class);

        $this->controller = new GenericMessageController($this->serializer, $this->bus, $this->validator);
    }

    public function testInvokeDispatchesMessageAndReturnsResult(): void
    {
        $messageClass = 'App\Message\MyMessage';
        $mockMessage = new \stdClass();
        $expectedResult = [
            'status' => 'ok',
        ];

        // Le controller fait json_encode($payload) avant de passer au deserializer
        $request = Request::create('/foo', 'POST', [], [], [], [], '{"name":"test"}');
        $request->attributes->set('_message_class', $messageClass);

        $this->serializer
            ->shouldReceive('deserialize')
            ->once()
            ->with('{"name":"test"}', $messageClass, 'json')
            ->andReturn($mockMessage);

        $this->validator->shouldReceive('validate')
            ->once()
            ->with($mockMessage);

        $envelope = new Envelope($mockMessage, [new HandledStamp($expectedResult, 'handler_name')]);
        $this->bus->shouldReceive('dispatch')
            ->once()
            ->with($mockMessage)
            ->andReturn($envelope);

        $response = ($this->controller)($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode($expectedResult), $response->getContent());
    }

    public function testGetWithoutBodyUsesQueryOnly(): void
    {
        $messageClass = 'App\Message\MyMessage';
        $mockMessage = new \stdClass();

        $request = Request::create('/foo', 'GET', [
            'page' => '1',
            'limit' => '10',
        ]);
        $request->attributes->set('_message_class', $messageClass);

        $this->serializer
            ->shouldReceive('deserialize')
            ->once()
            ->with('{"page":"1","limit":"10"}', $messageClass, 'json')
            ->andReturn($mockMessage);

        $this->validator->shouldReceive('validate')
            ->once();
        $envelope = new Envelope($mockMessage, [new HandledStamp(null, 'handler')]);
        $this->bus->shouldReceive('dispatch')
            ->once()
            ->andReturn($envelope);

        ($this->controller)($request);
    }

    public function testGetWithoutBodyAndQueryReturnsEmptyPayload(): void
    {
        $messageClass = 'App\Message\MyMessage';
        $mockMessage = new \stdClass();

        $request = Request::create('/health', 'GET');
        $request->attributes->set('_message_class', $messageClass);

        $this->serializer
            ->shouldReceive('deserialize')
            ->once()
            ->with('[]', $messageClass, 'json')
            ->andReturn($mockMessage);

        $this->validator->shouldReceive('validate')
            ->once();
        $envelope = new Envelope($mockMessage, [new HandledStamp(null, 'handler')]);
        $this->bus->shouldReceive('dispatch')
            ->once()
            ->andReturn($envelope);

        ($this->controller)($request);
    }

    public function testFilterDataDtoInterfaceBuildsEnvelope(): void
    {
        $messageClass = FilterDataCommandFixture::class;
        $mockMessage = new \stdClass();

        // Query params dans l'URL pour un PATCH
        $request = Request::create('/users/update?id=123', 'PATCH', [], [], [], [], '{"name":"Chloe"}');
        $request->attributes->set('_message_class', $messageClass);

        $this->serializer
            ->shouldReceive('deserialize')
            ->once()
            ->with(
                \Mockery::on(static function (string $json): bool {
                    $payload = json_decode($json, true);
                    return ($payload['filter'] ?? null) === [
                        'id' => '123',
                    ]
                        && ($payload['data'] ?? null) === [
                            'name' => 'Chloe',
                        ];
                }),
                $messageClass,
                'json'
            )
            ->andReturn($mockMessage);

        $this->validator->shouldReceive('validate')
            ->once();
        $envelope = new Envelope($mockMessage, [new HandledStamp([
            'ok' => true,
        ], 'handler')]);
        $this->bus->shouldReceive('dispatch')
            ->once()
            ->andReturn($envelope);

        $response = ($this->controller)($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRouteParamsAreInjectedIntoFilter(): void
    {
        $messageClass = FilterDataCommandFixture::class;
        $mockMessage = new \stdClass();

        $request = Request::create('/users/123', 'PATCH', [], [], [], [], '{"name":"Chloe"}');
        $request->attributes->set('_message_class', $messageClass);
        $request->attributes->set('id', '123'); // paramètre de route

        $this->serializer
            ->shouldReceive('deserialize')
            ->once()
            ->with(
                \Mockery::on(static function (string $json): bool {
                    $payload = json_decode($json, true);
                    return ($payload['filter'] ?? null) === [
                        'id' => '123',
                    ]
                        && ($payload['data'] ?? null) === [
                            'name' => 'Chloe',
                        ];
                }),
                $messageClass,
                'json'
            )
            ->andReturn($mockMessage);

        $this->validator->shouldReceive('validate')
            ->once();
        $envelope = new Envelope($mockMessage, [new HandledStamp(null, 'handler')]);
        $this->bus->shouldReceive('dispatch')
            ->once()
            ->andReturn($envelope);

        ($this->controller)($request);
    }

    public function testThrowsIfMessageClassMissing(): void
    {
        $request = Request::create('/foo', 'POST');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing message class.');

        ($this->controller)($request);
    }
}
