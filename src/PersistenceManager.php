<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PersistenceManager
{
    private static ?ManagerRegistry $managerRegistry = null;

    /**
     * @param object|string $objectOrClass
     */
    public static function repositoryFor($objectOrClass): RepositoryProxy
    {
        if ($objectOrClass instanceof Proxy) {
            $objectOrClass = $objectOrClass->object();
        }

        if (!\is_string($objectOrClass)) {
            $objectOrClass = \get_class($objectOrClass);
        }

        return new RepositoryProxy(self::managerRegistry()->getRepository($objectOrClass));
    }

    public static function persist(object $object): object
    {
        $objectManager = self::objectManagerFor($object);
        $objectManager->persist($object);
        $objectManager->flush();

        return $object;
    }

    public static function register(ManagerRegistry $managerRegistry): void
    {
        self::$managerRegistry = $managerRegistry;
    }

    /**
     * @param object|string $objectOrClass
     */
    public static function objectManagerFor($objectOrClass): ObjectManager
    {
        $class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass);

        if (!$objectManager = self::managerRegistry()->getManagerForClass($class)) {
            throw new \RuntimeException(\sprintf('No object manager registered for "%s".', $class));
        }

        return $objectManager;
    }

    private static function managerRegistry(): ManagerRegistry
    {
        if (null === self::$managerRegistry) {
            throw new \RuntimeException('ManagerRegistry not registered...'); // todo improve
        }

        return self::$managerRegistry;
    }
}
