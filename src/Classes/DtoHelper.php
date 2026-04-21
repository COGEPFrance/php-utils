<?php

namespace Cogep\PhpUtils\Classes;

use Cogep\PhpUtils\Exceptions\MethodHasNoParamException;
use Cogep\PhpUtils\Exceptions\ObjectMustBeClassException;
use ReflectionNamedType;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DtoHelper
{
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
    ) {
    }

    /**
     * @return class-string<DTOInterface>
     */
    public function getDtoClassFromMethod(object $handler, string $methodName): string
    {
        $reflection = new \ReflectionMethod($handler, $methodName);
        $parameters = $reflection->getParameters();

        if (! isset($parameters[0])) {
            throw new MethodHasNoParamException($methodName);
        }

        $type = $parameters[0]->getType();

        $dtoClass = ($type instanceof ReflectionNamedType && ! $type->isBuiltin())
            ? $type->getName()
            : (string) $type;

        if ($dtoClass !== DTOInterface::class && ! is_subclass_of($dtoClass, DTOInterface::class)) {
            throw new ObjectMustBeClassException($dtoClass, DTOInterface::class);
        }

        return $dtoClass;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function fillDtoFromMethodWithGivenData(object $handler, string $methodName, array $data): DTOInterface
    {
        $dtoClass = $this->getDtoClassFromMethod($handler, $methodName);
        return $this->denormalizer->denormalize($data, $dtoClass);
    }
}
