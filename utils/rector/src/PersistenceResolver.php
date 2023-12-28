<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\ObjectFactory;

/**
 * This class will guess if a target class is persisted.
 * This leverages phpstan/phpstan-doctrine.
 *
 * By default it only uses AttributeDriver or AnnotationDriver, but one can provide an "objectManagerLoader"
 * which can read any Doctrine's mapping (even the ones declared outside the entities)
 *
 * @see https://github.com/phpstan/phpstan-doctrine#configuration
 */
final class PersistenceResolver
{
    /** @var array<class-string<ObjectFactory>, class-string> */
    private array $factoryToTargetClass = [];

    private ObjectMetadataResolver $doctrineMetadataResolver;

    public function __construct(string|null $objectManagerLoader = null)
    {
        $this->doctrineMetadataResolver = new ObjectMetadataResolver($objectManagerLoader, sys_get_temp_dir().'/rector-doctrine');
    }

    /** @param class-string<ObjectFactory> $factoryClass */
    public function shouldTransformFactoryIntoObjectFactory(string $factoryClass): bool
    {
        $targetClass = $this->targetClass($factoryClass);

        return !$this->shouldUseProxyFactory($targetClass);
    }

    /**
     * @param class-string $targetClass
     */
    public function shouldUseProxyFactory(string $targetClass): bool
    {
        return !(new \ReflectionClass($targetClass))->isFinal() && $this->isPersisted($targetClass);
    }

    /**
     * @param class-string<ObjectFactory> $factoryClass
     * @return class-string
     */
    private function targetClass(string $factoryClass): string
    {
        return $this->factoryToTargetClass[$factoryClass] ??= (static function () use ($factoryClass) {
            return is_a($factoryClass, ModelFactory::class, allow_string: true)
                ? (new \ReflectionClass($factoryClass))->getMethod('getClass')->invoke(null)
                : $factoryClass::class();
        })();
    }

    private function isPersisted(string $targetClass): bool
    {
        return (bool) $this->doctrineMetadataResolver->getClassMetadata($targetClass);
    }
}
