<?php

namespace Unit\Classes\Dtos;

use Cogep\PhpUtils\Classes\Dtos\DtoHelper;
use Cogep\PhpUtils\Exceptions\MethodHasNoParamException;
use Cogep\PhpUtils\Exceptions\ObjectMustBeClassException;
use Cogep\PhpUtils\Tests\Classes\DummyDynamicDTO;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DtoHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $this->denormalizer = Mockery::mock(DenormalizerInterface::class);
        $this->helper = new DtoHelper($this->denormalizer);
        parent::setUp();
    }

    public function classTest(): object
    {
        return new class() {
            public function okMethod(DummyDynamicDTO $dto): DummyDynamicDTO
            {
                return $dto;
            }

            public function noParamMethod(): void
            {
            }

            public function noDtoFirstParam(int $id): int
            {
                return $id;
            }
        };
    }

    public function testDtoClassFromMethodSuccess(): void
    {
        $response = $this->helper->getDtoClassFromMethod($this->classTest(), 'okMethod');
        $this->assertEquals(DummyDynamicDTO::class, $response);
    }

    public function testDtoClassFromMethodWithtoutParam(): void
    {
        $this->expectException(MethodHasNoParamException::class);
        $this->helper->getDtoClassFromMethod($this->classTest(), 'noParamMethod');
    }

    public function testDtoClassFromMethodWithFirstParamNotDtoInterface(): void
    {
        $this->expectException(ObjectMustBeClassException::class);
        $this->helper->getDtoClassFromMethod($this->classTest(), 'noDtoFirstParam');
    }

    public function testFillDtoFromMethodWithGivenData(): void
    {
        $data = [
            'a' => 'test',
        ];
        $expectedDto = new DummyDynamicDTO();
        $expectedDto->__set('a', 'test');

        $this->denormalizer
            ->shouldReceive('denormalize')
            ->once()
            ->with($data, $expectedDto::class)
            ->andReturn($expectedDto);

        $dto = $this->helper->fillDtoFromMethodWithGivenData($this->classTest(), 'okMethod', $data);

        $this->assertEquals($expectedDto, $dto);
    }
}
