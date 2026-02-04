<?php

namespace Cogep\PhpUtils\Mapping;

use Cogep\PhpUtils\Classes\Dtos\DTOInterface;
use Cogep\PhpUtils\Classes\DynamicProperties\DynamicPropertyClass;
use Cogep\PhpUtils\Classes\EntityInterface;

class DtoEntityMapper
{
    public function __construct()
    {
    }

    /**
     * @template T of EntityInterface
     *
     * @param iterable<DTOInterface> $dtos Liste de DTOs
     * @param class-string<T>|T $entity Entité finale
     *
     * @return T
     */
    public function mergeDtosToEntity(iterable $dtos, string|EntityInterface $entity): EntityInterface
    {
        $entity = is_string($entity) ? new $entity() : $entity;

        foreach ($dtos as $dto) {
            $data = get_object_vars($dto);

            foreach ($data as $key => $value) {
                $this->hydrateProperty($entity, $key, $value);
            }
        }

        return $entity;
    }

    /**
     * @template T of EntityInterface
     *
     * @param class-string<T>|T $entity Entité finale
     *
     * @return T
     */
    public function dtoToEntity(DTOInterface $dto, string|EntityInterface $entity): EntityInterface
    {
        return $this->mergeDtosToEntity([$dto], $entity);
    }

    private function hydrateProperty(EntityInterface $entity, string $key, mixed $value): void
    {
        if ($value === null || isset($entity->{$key})) {
            return;
        }

        if ($entity instanceof DynamicPropertyClass) {
            $entity->__set($key, $value);
            return;
        }

        $setter = 'set' . ucfirst($key);
        if (method_exists($entity, $setter)) {
            $entity->{$setter}($value);
            return;
        }

        if (property_exists($entity, $key)) {
            $entity->{$key} = $value;
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            "La classe %s n'a pas d'attribut ou de setter pour '%s'.",
            $entity::class,
            $key
        ));
    }
}
