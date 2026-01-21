<?php

namespace Cogep\PhpUtils\Mapping;

use Cogep\PhpUtils\Classes\DTOInterface;
use Cogep\PhpUtils\Classes\DynamicProperties\DynamicPropertyClass;
use Cogep\PhpUtils\Classes\EntityInterface;

class DtoEntityMapper
{
    public function __construct()
    {
    }

    /**
     * @param iterable<DTOInterface> $dtos Liste de DTOs
     * @param class-string<EntityInterface>|EntityInterface $entity Entité finale
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

    private function hydrateProperty(EntityInterface $entity, string $key, mixed $value): void
    {
        if ($value === null || isset($entity->{$key})) {
            return;
        }

        if ($entity instanceof DynamicPropertyClass) {
            $entity->__set($key, $value);
            return;
        }

        if (property_exists($entity, $key)) {
            $entity->{$key} = $value;
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            "La classe %s n'a pas d'attribut %s et n'accepte pas de propriétés dynamiques.",
            $entity::class,
            $key
        ));
    }
}
