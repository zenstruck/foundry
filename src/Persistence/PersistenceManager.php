<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\ORM\AbstractORMPersistenceStrategy;
use Zenstruck\Foundry\Persistence\Exception\NoPersistenceStrategy;
use Zenstruck\Foundry\Persistence\Exception\RefreshObjectFailed;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class PersistenceManager
{
    private static bool $hasDatabaseBeenReset = false;
    private static bool $ormOnly = false;

    private bool $flush = true;
    private bool $persist = true;

    /**
     * @param PersistenceStrategy[] $strategies
     */
    public function __construct(private iterable $strategies)
    {
    }

    public static function isDAMADoctrineTestBundleEnabled(): bool
    {
        return \class_exists(StaticDriver::class) && StaticDriver::isKeepStaticConnections();
    }

    /**
     * @param callable():KernelInterface $createKernel
     * @param callable():void            $shutdownKernel
     */
    public static function resetDatabase(callable $createKernel, callable $shutdownKernel): void
    {
        if (self::$hasDatabaseBeenReset) {
            return;
        }

        if ($isDAMADoctrineTestBundleEnabled = self::isDAMADoctrineTestBundleEnabled()) {
            // disable static connections for this operation
            // :warning: the kernel should not be booted before calling this!
            StaticDriver::setKeepStaticConnections(false);
        }

        $kernel = $createKernel();
        $configuration = Configuration::instance();
        $strategyClasses = [];

        try {
            $strategies = $configuration->persistence()->strategies;
        } catch (PersistenceNotAvailable $e) {
            if (!\class_exists(TestKernel::class)) {
                throw $e;
            }

            // allow this to fail if running foundry test suite
            return;
        }

        foreach ($strategies as $strategy) {
            $strategy->resetDatabase($kernel);
            $strategyClasses[] = $strategy::class;
        }

        if (1 === \count($strategyClasses) && \is_a($strategyClasses[0], AbstractORMPersistenceStrategy::class, allow_string: true)) {
            // enable skipping booting the kernel for resetSchema()
            self::$ormOnly = true;
        }

        if ($isDAMADoctrineTestBundleEnabled && self::$ormOnly) {
            // add global stories so they are available after transaction rollback
            $configuration->stories->loadGlobalStories();
        }

        if ($isDAMADoctrineTestBundleEnabled) {
            // re-enable static connections
            StaticDriver::setKeepStaticConnections(true);
        }

        $shutdownKernel();

        self::$hasDatabaseBeenReset = true;
    }

    /**
     * @param callable():KernelInterface $createKernel
     * @param callable():void            $shutdownKernel
     */
    public static function resetSchema(callable $createKernel, callable $shutdownKernel): void
    {
        if (self::canSkipSchemaReset()) {
            // can fully skip booting the kernel
            return;
        }

        $kernel = $createKernel();
        $configuration = Configuration::instance();

        try {
            $strategies = $configuration->persistence()->strategies;
        } catch (PersistenceNotAvailable $e) {
            if (!\class_exists(TestKernel::class)) {
                throw $e;
            }

            // allow this to fail if running foundry test suite
            return;
        }

        foreach ($strategies as $strategy) {
            $strategy->resetSchema($kernel);
        }

        $configuration->stories->loadGlobalStories();

        $shutdownKernel();
    }

    public function isEnabled(): bool
    {
        return $this->persist;
    }

    public function disablePersisting(): void
    {
        $this->persist = false;
    }

    public function enablePersisting(): void
    {
        $this->persist = true;
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function save(object $object): object
    {
        if ($object instanceof Proxy) {
            return $object->_save();
        }

        $om = $this->strategyFor($object::class)->objectManagerFor($object::class);
        $om->persist($object);
        $this->flush($om);

        return $object;
    }

    /**
     * @param callable():void $callback
     */
    public function flushAfter(callable $callback): void
    {
        $this->flush = false;

        $callback();

        $this->flush = true;

        foreach ($this->strategies as $strategy) {
            foreach ($strategy->objectManagers() as $om) {
                $this->flush($om);
            }
        }
    }

    public function flush(ObjectManager $om): void
    {
        if ($this->flush) {
            $om->flush();
        }
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function refresh(object &$object): object
    {
        if (!$this->flush) {
            return $object;
        }

        if ($object instanceof Proxy) {
            return $object->_refresh();
        }

        $strategy = $this->strategyFor($object::class);

        if ($strategy->hasChanges($object)) {
            throw RefreshObjectFailed::objectHasUnsavedChanges($object::class);
        }

        $om = $strategy->objectManagerFor($object::class);

        if ($strategy->contains($object)) {
            try {
                $om->refresh($object);
            } catch (\LogicException|\Error) {
                // prevent entities/documents with readonly properties to create an error
                // LogicException is for ORM / Error is for ODM
                // @see https://github.com/doctrine/orm/issues/9505
            }

            return $object;
        }

        if ($strategy->isEmbeddable($object)) {
            return $object;
        }

        $id = $om->getClassMetadata($object::class)->getIdentifierValues($object);

        if (!$id || !$object = $om->find($object::class, $id)) {
            throw RefreshObjectFailed::objectNoLongExists();
        }

        return $object;
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function delete(object $object): object
    {
        if ($object instanceof Proxy) {
            return $object->_delete();
        }

        $om = $this->strategyFor($object::class)->objectManagerFor($object::class);
        $om->remove($object);
        $this->flush($om);

        return $object;
    }

    /**
     * @param class-string $class
     */
    public function truncate(string $class): void
    {
        $class = unproxy($class);

        $this->strategyFor($class)->truncate($class);
    }

    /**
     * @param class-string $class
     */
    public function autoPersist(string $class): bool
    {
        return $this->strategyFor(unproxy($class))->autoPersist();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return ObjectRepository<T>
     */
    public function repositoryFor(string $class): ObjectRepository
    {
        $class = unproxy($class);

        return $this->strategyFor($class)->objectManagerFor($class)->getRepository($class);
    }

    /**
     * @param class-string $parent
     * @param class-string $child
     */
    public function relationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        $parent = unproxy($parent);
        $child = unproxy($child);

        return $this->strategyFor($parent)->relationshipMetadata($parent, $child, $field);
    }

    /**
     * @param class-string $class
     */
    public function metadataFor(string $class): ClassMetadata
    {
        return $this->strategyFor($class)->classMetadata($class);
    }

    /**
     * @return iterable<ClassMetadata>
     */
    public function allMetadata(): iterable
    {
        foreach ($this->strategies as $strategy) {
            foreach ($strategy->objectManagers() as $objectManager) {
                yield from $objectManager->getMetadataFactory()->getAllMetadata();
            }
        }
    }

    /**
     * @return list<string>
     */
    public function managedNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->strategies as $strategy) {
            $namespaces[] = $strategy->managedNamespaces();
        }

        return \array_values(\array_unique(\array_merge(...$namespaces)));
    }

    /**
     * @param class-string $owner
     *
     * @return array<string,mixed>|null
     */
    public function embeddablePropertiesFor(object $object, string $owner): ?array
    {
        $owner = unproxy($owner);

        try {
            return $this->strategyFor($owner)->embeddablePropertiesFor(unproxy($object), $owner);
        } catch (NoPersistenceStrategy) {
            return null;
        }
    }

    public function hasPersistenceFor(object $object): bool
    {
        try {
            return (bool) $this->strategyFor($object::class);
        } catch (NoPersistenceStrategy) {
            return false;
        }
    }

    private static function canSkipSchemaReset(): bool
    {
        return self::$ormOnly && self::isDAMADoctrineTestBundleEnabled();
    }

    /**
     * @param class-string $class
     *
     * @throws NoPersistenceStrategy if no persistence strategy found
     */
    private function strategyFor(string $class): PersistenceStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($class)) {
                return $strategy;
            }
        }

        throw new NoPersistenceStrategy($class);
    }
}
