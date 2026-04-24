<?php

namespace Cogep\PhpUtils\Helpers;

use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidator
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    public function validate(object $entity): void
    {
        $violations = $this->validator->validate($entity);

        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
            }

            $errorsString = implode(', ', $messages);
            throw new DomainException(ErrorCodeEnum::INVALID_INPUT, $errorsString);
        }
    }
}
