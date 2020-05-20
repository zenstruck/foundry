<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PersistenceManager
{
    private static ?ManagerRegistry $managerRegistry = null;

    /**
     * @param object|string $objectOrClass
     *
     * @return RepositoryProxy|ObjectRepository
     */
    public static function repositoryFor($objectOrClass, bool $proxy = true): ObjectRepository
    {
        if ($objectOrClass instanceof Proxy) {
            $objectOrClass = $objectOrClass->object();
        }

        if (!\is_string($objectOrClass)) {
            $objectOrClass = \get_class($objectOrClass);
        }

        $objectManager = self::objectManagerFor($objectOrClass);
        $repository = $objectManager->getRepository($objectOrClass);

        return $proxy ? new RepositoryProxy($repository) : $repository;
    }

    /**
     * @return Proxy|object
     */
    public static function persist(object $object, bool $proxy = true): object
    {
        $objectManager = self::objectManagerFor($object);
        $objectManager->persist($object);
        $objectManager->flush();

        return $proxy ? self::proxy($object) : $object;
    }

    public static function proxy(object $object): Proxy
    {
        if ($object instanceof Proxy) {
            return $object;
        }

        return new Proxy($object);
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
        if (null === self::$managerRegistry) {
            throw new \RuntimeException('ManagerRegistry not registered...'); // todo improve
        }

        $class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass);

        if (!$objectManager = self::$managerRegistry->getManagerForClass($class)) {
            throw new \RuntimeException(\sprintf('No object manager registered for "%s".', $class));
        }

        return $objectManager;
    }
}
