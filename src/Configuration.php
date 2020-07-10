<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Faker;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Configuration
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var StoryManager */
    private $stories;

    /** @var Faker\Generator */
    private $faker;

    /** @var callable */
    private $instantiator;

    /** @var bool */
    private $defaultProxyAutoRefresh = false;

    public function __construct(ManagerRegistry $managerRegistry, StoryManager $storyManager)
    {
        $this->managerRegistry = $managerRegistry;
        $this->stories = $storyManager;
        $this->faker = Faker\Factory::create();
        $this->instantiator = new Instantiator();
    }

    public function stories(): StoryManager
    {
        return $this->stories;
    }

    public function faker(): Faker\Generator
    {
        return $this->faker;
    }

    public function instantiator(): callable
    {
        return $this->instantiator;
    }

    public function defaultProxyAutoRefresh(): bool
    {
        return $this->defaultProxyAutoRefresh;
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

    public function alwaysAutoRefreshProxies(): self
    {
        $this->defaultProxyAutoRefresh = true;

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

        return new RepositoryProxy($this->managerRegistry->getRepository($objectOrClass));
    }

    /**
     * @param object|string $objectOrClass
     */
    public function objectManagerFor($objectOrClass): ObjectManager
    {
        $class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass);

        if (!$objectManager = $this->managerRegistry->getManagerForClass($class)) {
            throw new \RuntimeException(\sprintf('No object manager registered for "%s".', $class));
        }

        return $objectManager;
    }
}
