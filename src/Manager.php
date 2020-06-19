<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Psr\Container\ContainerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Manager
{
    private ?ManagerRegistry $managerRegistry;
    private Faker\Generator $faker;

    /** @var callable */
    private $instantiator;

    public function __construct(?ManagerRegistry $managerRegistry = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->faker = Faker\Factory::create();
        $this->instantiator = new Instantiator();
    }

    public static function boot(ContainerInterface $container, ?ManagerRegistry $managerRegistry = null): void
    {
        $manager = $container->has(self::class) ? $container->get(self::class) : new self();

        if ($managerRegistry) {
            $manager->setManagerRegistry($managerRegistry);
        }

        Factory::boot($manager);
    }

    public function faker(): Faker\Generator
    {
        return $this->faker;
    }

    public function instantiator(): callable
    {
        return $this->instantiator;
    }

    public function setManagerRegistry(ManagerRegistry $managerRegistry): self
    {
        $this->managerRegistry = $managerRegistry;

        return $this;
    }

    public function setInstantiator(callable $instantiator): self
    {
        $this->instantiator = $instantiator;

        return $this;
    }

    public function setFaker(Faker\Generator $faker): self
    {
        $this->faker = $faker;

        return $this;
    }

    /**
     * @param object|string $objectOrClass
     */
    public function repositoryFor($objectOrClass): RepositoryProxy
    {
        if ($objectOrClass instanceof Proxy) {
            $objectOrClass = $objectOrClass->object();
        }

        if (!\is_string($objectOrClass)) {
            $objectOrClass = \get_class($objectOrClass);
        }

        return new RepositoryProxy($this->managerRegistry()->getRepository($objectOrClass), $this);
    }

    /**
     * @param object|string $objectOrClass
     */
    public function objectManagerFor($objectOrClass): ObjectManager
    {
        $class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass);

        if (!$objectManager = $this->managerRegistry()->getManagerForClass($class)) {
            throw new \RuntimeException(\sprintf('No object manager registered for "%s".', $class));
        }

        return $objectManager;
    }

    private function managerRegistry(): ManagerRegistry
    {
        if (!$this->managerRegistry) {
            throw new \RuntimeException('ManagerRegistry not set.'); // todo
        }

        return $this->managerRegistry;
    }
}
