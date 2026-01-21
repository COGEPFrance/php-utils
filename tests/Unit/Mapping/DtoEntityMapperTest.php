<?php

namespace Unit\Mapping;

use Cogep\PhpUtils\Mapping\DtoEntityMapper;
use Cogep\PhpUtils\Tests\DummyDynamicDTO;
use Cogep\PhpUtils\Tests\DummyDynamicEntity;
use PHPUnit\Framework\TestCase;

class DtoEntityMapperTest extends TestCase
{
    private DtoEntityMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new DtoEntityMapper();
    }

    public function testMergeDtosToEntityHydratesCorrectly(): void
    {
        $dto1 = new DummyDynamicDTO();
        $dto1->name = 'name';

        $dto2 = new DummyDynamicDTO();
        $dto2->id = 123;
        $dto2->name = 'name2';

        $dtos = [$dto1, $dto2];
        $result = $this->mapper->mergeDtosToEntity($dtos, DummyDynamicEntity::class);

        $this->assertInstanceOf(DummyDynamicEntity::class, $result);
        $this->assertEquals(123, $result->id);
        $this->assertEquals('name', $result->name);
    }

    public function testMergeDtosSkipsEmptyAndNullValues(): void
    {
        $entity = new DummyDynamicEntity();
        $entity->name = 'keep_me';

        $dto = new DummyDynamicDTO();
        $dto->name = 'name';

        $entity = $this->mapper->mergeDtosToEntity([$dto], $entity);

        $this->assertInstanceOf(DummyDynamicEntity::class, $entity);
        $this->assertEquals('keep_me', $entity->name);
    }
}
