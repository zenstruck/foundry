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

namespace Zenstruck\Foundry\Persistence;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document as ODMDocumentAttribute;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\Entity as ORMEntityAttribute;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Foundry\Exception\FoundryBootException;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @internal
 */
final class PersistenceManager
{
    private ManagerRegistry|null $managerRegistry = null;

    private bool $flushEnabled = true;

    private bool $persistEnabled = true;

    private static AnnotationReader|null $annotationReader = null;

    /**
     * @param class-string $class
     */
    public function objectManagerFor(string $class): ObjectManager
    {
        if (!$objectManager = $this->managerRegistry()->getManagerForClass($class)) {
            throw new \RuntimeException(\sprintf('No object manager registered for "%s".', $class));
        }

        return $objectManager;
    }

    /** @phpstan-assert-if-true !null $this->managerRegistry */
    public function hasManagerRegistry(): bool
    {
        return null !== $this->managerRegistry;
    }

    /**
     * @template T of object
     * @param class-string<T>|T $objectOrClass
     *
     * @return RepositoryProxy<T>
     */
    public function repositoryFor(object|string $objectOrClass): RepositoryProxy
    {
        if (!$this->isPersistEnabled()) {
            throw new \RuntimeException('Cannot get repository when persist is disabled.');
        }

        if ($objectOrClass instanceof Proxy) {
            $objectOrClass = $objectOrClass->object();
        }

        if (!\is_string($objectOrClass)) {
            $objectOrClass = $objectOrClass::class;
        }

        /** @var EntityRepository<T> $repository */
        $repository = $this->managerRegistry()->getRepository($objectOrClass);

        return new RepositoryProxy($repository);
    }

    public function isFlushingEnabled(): bool
    {
        return $this->flushEnabled;
    }

    public function delayFlush(callable $callback): mixed
    {
        $this->flushEnabled = false;

        $result = $callback();

        foreach ($this->managerRegistry()->getManagers() as $manager) {
            $manager->flush();
        }

        $this->flushEnabled = true;

        return $result;
    }

    public function setManagerRegistry(ManagerRegistry $managerRegistry): self
    {
        $this->managerRegistry = $managerRegistry;

        return $this;
    }

    public function disablePersist(): void
    {
        $this->persistEnabled = false;
    }

    public function enablePersist(): void
    {
        $this->persistEnabled = true;
    }

    public function isPersistEnabled(): bool
    {
        return true === $this->persistEnabled;
    }

    /**
     * @param class-string $targetClass
     */
    public static function classCanBePersisted(string $targetClass): bool
    {
        $reflectionClass = new \ReflectionClass($targetClass);

        $attributes = $reflectionClass->getAttributes(ORMEntityAttribute::class);
        if (!$attributes) {
            $attributes = $reflectionClass->getAttributes(ODMDocumentAttribute::class);
        }

        if (!$attributes) {
            $attributes = self::annotationReader()?->getClassAnnotation($reflectionClass, ORMEntityAttribute::class);
        }

        if (!$attributes) {
            $attributes = self::annotationReader()?->getClassAnnotation($reflectionClass, ODMDocumentAttribute::class);
        }

        return (bool) $attributes;
    }

    private function managerRegistry(): ManagerRegistry
    {
        if (!$this->hasManagerRegistry()) {
            throw FoundryBootException::notBootedWithDoctrine();
        }

        return $this->managerRegistry;
    }

    private static function annotationReader(): AnnotationReader|null
    {
        if (\class_exists(AnnotationReader::class)) {
            return self::$annotationReader ??= new AnnotationReader();
        }

        return null;
    }
}
