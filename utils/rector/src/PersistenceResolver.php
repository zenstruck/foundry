<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Utils\Rector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
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
 *
 * todo: rename some methods
 */
final class PersistenceResolver
{
    /** @var array<class-string<ObjectFactory>, class-string> */
    private array $factoryToTargetClass = [];

    private ObjectMetadataResolver $doctrineMetadataResolver;

    public function __construct(?string $objectManagerLoader = null)
    {
        $this->doctrineMetadataResolver = new ObjectMetadataResolver($objectManagerLoader, \sys_get_temp_dir().'/rector-doctrine'); // @phpstan-ignore-line
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
     * @param class-string $targetClass
     */
    public function isMongoDocument(string $targetClass): bool
    {
        // phpstan/phpstan-doctrine does not allow to determine if a class is managed by ODM
        // so let's do a "best effort" et check if the class has a doctrine attribute
        return (new \ReflectionClass($targetClass))->getAttributes(Document::class)
            || (new \ReflectionClass($targetClass))->getAttributes(MappedSuperclass::class);
    }

    /**
     * @param  class-string<ObjectFactory> $factoryClass
     * @return class-string
     */
    public function targetClass(string $factoryClass): string
    {
        return $this->factoryToTargetClass[$factoryClass] ??= (static function() use ($factoryClass) {
            return \is_a($factoryClass, ModelFactory::class, allow_string: true)
                ? (new \ReflectionClass($factoryClass))->getMethod('getClass')->invoke(null)
                : $factoryClass::class();
        })();
    }

    /**
     * @param  class-string                   $targetClass
     * @return class-string<ObjectRepository>
     */
    public function geRepositoryClass($targetClass): string
    {
        $mongoAttribute = $this->getMongoAttribute($targetClass);

        if ($mongoAttribute) {
            return $mongoAttribute->repositoryClass ?? DocumentRepository::class; // @phpstan-ignore-line
        }

        return $this->doctrineMetadataResolver->getClassMetadata($targetClass)?->customRepositoryClassName ?? EntityRepository::class;  // @phpstan-ignore-line
    }

    /**
     * @param class-string $targetClass
     */
    private function isPersisted(string $targetClass): bool
    {
        $isPersisted = (bool) $this->doctrineMetadataResolver->getClassMetadata($targetClass); // @phpstan-ignore-line

        if ($isPersisted || !\class_exists(DocumentManager::class)) {
            return $isPersisted;
        }

        return $this->isMongoDocument($targetClass);
    }

    /**
     * @param class-string $targetClass
     */
    private function getMongoAttribute(string $targetClass): MappedSuperclass|Document|null
    {
        $reflectionClass = new \ReflectionClass($targetClass);

        $attributes = $reflectionClass->getAttributes(Document::class) ?: $reflectionClass->getAttributes(MappedSuperclass::class);

        if (!$attributes) {
            return null;
        }

        return $attributes[0]->newInstance();
    }
}
