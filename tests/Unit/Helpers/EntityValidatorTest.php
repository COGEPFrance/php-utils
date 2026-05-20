<?php

namespace Cogep\PhpUtils\Tests\Unit\Helpers;

use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Cogep\PhpUtils\Helpers\EntityValidator;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidatorTest extends BaseMockeryTestCase
{
    private ValidatorInterface $validatorMock;

    private EntityValidator $entityValidator;

    protected function setUp(): void
    {
        $this->validatorMock = \Mockery::mock(ValidatorInterface::class);
        $this->entityValidator = new EntityValidator($this->validatorMock);
    }

    public function testValidateSuccess(): void
    {
        $entity = new \stdClass();

        $this->validatorMock
            ->shouldReceive('validate')
            ->once()
            ->with($entity)
            ->andReturn(new ConstraintViolationList());

        $this->entityValidator->validate($entity);
    }

    public function testValidateThrowsDomainExceptionOnViolations(): void
    {
        $entity = new \stdClass();

        $violation1 = \Mockery::mock(ConstraintViolation::class);
        $violation1->shouldReceive('getPropertyPath')
            ->andReturn('email');
        $violation1->shouldReceive('getMessage')
            ->andReturn('Invalide');

        $violation2 = \Mockery::mock(ConstraintViolation::class);
        $violation2->shouldReceive('getPropertyPath')
            ->andReturn('age');
        $violation2->shouldReceive('getMessage')
            ->andReturn('Trop jeune');

        $violations = new ConstraintViolationList([$violation1, $violation2]);

        $this->validatorMock
            ->shouldReceive('validate')
            ->once()
            ->with($entity)
            ->andReturn($violations);

        try {
            $this->entityValidator->validate($entity);
            $this->fail('Une DomainException aurait dû être jetée.');
        } catch (DomainException $e) {
            $this->assertEquals(ErrorCodeEnum::INVALID_INPUT, $e->getErrorCode());
            $this->assertStringContainsString('email: Invalide', $e->getMessage());
            $this->assertStringContainsString('age: Trop jeune', $e->getMessage());
        }
    }
}
