<?php

namespace Cogep\PhpUtils\Tests\PHPStan;

use Cogep\PhpUtils\Classes\FilterDataDtoInterface;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Vérifie que toute classe implémentant FilterDataDtoInterface :
 *  - déclare $filter et $data typés avec des classes concrètes (pas object, array, mixed)
 *  - a #[Assert\Valid] et #[Assert\NotNull] sur ces deux propriétés.
 *
 * @implements Rule<Class_>
 */
class FilterDataDtoRule implements Rule
{
    private const FORBIDDEN_TYPES = ['object', 'array', 'mixed', 'null'];

    private const REQUIRED_ATTRIBUTES = [
        'Symfony\\Component\\Validator\\Constraints\\Valid',
        'Symfony\\Component\\Validator\\Constraints\\NotNull',
    ];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name === null) {
            return [];
        }

        $className = $scope->getNamespace() !== null
            ? $scope->getNamespace() . '\\' . $node->name->toString()
            : $node->name->toString();

        if (! $this->reflectionProvider->hasClass($className)) {
            return [];
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (! $classReflection->implementsInterface(FilterDataDtoInterface::class)) {
            return [];
        }

        $nativeReflection = $classReflection->getNativeReflection();
        $errors = [];

        foreach (['filter', 'data'] as $propertyName) {
            /** @phpstan-ignore argument.type */
            array_push($errors, ...$this->checkProperty($nativeReflection, $className, $propertyName));
        }

        return $errors;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<IdentifierRuleError>
     */
    private function checkProperty(\ReflectionClass $reflection, string $className, string $propertyName): array
    {
        if (! $reflection->hasProperty($propertyName)) {
            return [
                RuleErrorBuilder::message(
                    sprintf('Class %s implements FilterDataDtoInterface but is missing property $%s.', $className, $propertyName)
                )->identifier('filterDataDto.missingProperty')
                    ->build(),
            ];
        }

        $property = $reflection->getProperty($propertyName);
        $errors = [];

        $typeError = $this->checkPropertyType($property, $className, $propertyName);
        if ($typeError !== null) {
            $errors[] = $typeError;
        }

        array_push($errors, ...$this->checkPropertyAttributes($property, $className, $propertyName));

        return $errors;
    }

    private function checkPropertyType(\ReflectionProperty $property, string $className, string $propertyName): ?IdentifierRuleError
    {
        $type = $property->getType();

        if ($type === null) {
            return RuleErrorBuilder::message(
                sprintf('Property $%s of %s must be typed with a concrete class, not untyped.', $propertyName, $className)
            )->identifier('filterDataDto.forbiddenType')
                ->build();
        }

        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : (string) $type;

        if (in_array(strtolower($typeName), self::FORBIDDEN_TYPES, true)) {
            return RuleErrorBuilder::message(
                sprintf('Property $%s of %s must be typed with a concrete class, "%s" is not allowed.', $propertyName, $className, $typeName)
            )->identifier('filterDataDto.forbiddenType')
                ->build();
        }

        return null;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkPropertyAttributes(\ReflectionProperty $property, string $className, string $propertyName): array
    {
        $attributeNames = array_map(
            static fn (\ReflectionAttribute $attr): string => $attr->getName(),
            $property->getAttributes()
        );

        $errors = [];

        foreach (self::REQUIRED_ATTRIBUTES as $required) {
            $shortName = substr($required, (int) strrpos($required, '\\') + 1);
            $hasAttribute = in_array($required, $attributeNames, true) || in_array($shortName, $attributeNames, true);

            if (! $hasAttribute) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf('Property $%s of %s is missing #[%s] constraint.', $propertyName, $className, $shortName)
                )->identifier('filterDataDto.missingConstraint')
                    ->build();
            }
        }

        return $errors;
    }
}
