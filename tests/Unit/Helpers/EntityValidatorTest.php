<?php

namespace Cogep\PhpUtils\Tests\Unit\Helpers;

use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Cogep\PhpUtils\Helpers\EntityValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidatorTest extends TestCase
{
    private ValidatorInterface $validatorMock;

    private EntityValidator $entityValidator;

    protected function setUp(): void
    {
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
        $this->entityValidator = new EntityValidator($this->validatorMock);
    }

    public function testValidateSuccess(): void
    {
        $entity = new \stdClass();

        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($entity)
            ->willReturn(new ConstraintViolationList());

        $this->entityValidator->validate($entity);
    }

    public function testValidateThrowsDomainExceptionOnViolations(): void
    {
        $entity = new \stdClass();

        $violation1 = $this->createMock(ConstraintViolation::class);
        $violation1->method('getPropertyPath')
            ->willReturn('email');
        $violation1->method('getMessage')
            ->willReturn('Invalide');

        $violation2 = $this->createMock(ConstraintViolation::class);
        $violation2->method('getPropertyPath')
            ->willReturn('age');
        $violation2->method('getMessage')
            ->willReturn('Trop jeune');

        $violations = new ConstraintViolationList([$violation1, $violation2]);

        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($entity)
            ->willReturn($violations);

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
