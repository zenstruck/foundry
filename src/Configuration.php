<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Configuration
{
    /** @var ManagerRegistry|null */
    private $managerRegistry;

    /** @var StoryManager */
    private $stories;

    /** @var ModelFactoryManager */
    private $factories;

    /** @var Faker\Generator */
    private $faker;

    /** @var callable */
    private $instantiator;

    /** @var bool|null */
    private $defaultProxyAutoRefresh;

    /** @var bool */
    private $flushEnabled = true;

    /** @var bool */
    private $databaseResetEnabled = true;

    /** @var list<string> */
    private $ormConnectionsToReset;

    /** @var list<string> */
    private $ormObjectManagersToReset;

    /** @var string */
    private $ormResetMode;

    /** @var list<string> */
    private $odmObjectManagersToReset;

    public function __construct(array $ormConnectionsToReset, array $ormObjectManagersToReset, string $ormResetMode, array $odmObjectManagersToReset)
    {
        $this->stories = new StoryManager([]);
        $this->factories = new ModelFactoryManager([]);
        $this->faker = Faker\Factory::create();
        $this->instantiator = new Instantiator();

        $this->ormConnectionsToReset = $ormConnectionsToReset;
        $this->ormObjectManagersToReset = $ormObjectManagersToReset;
        $this->ormResetMode = $ormResetMode;
        $this->odmObjectManagersToReset = $odmObjectManagersToReset;
    }

    public function stories(): StoryManager
    {
        return $this->stories;
    }

    public function factories(): ModelFactoryManager
    {
        return $this->factories;
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
        if (!$this->hasManagerRegistry()) {
            return false;
        }

        if (null === $this->defaultProxyAutoRefresh) {
            trigger_deprecation('zenstruck\foundry', '1.9', 'Not explicitly configuring the default proxy auto-refresh is deprecated and will default to "true" in 2.0. Use "zenstruck_foundry.auto_refresh_proxies" in the bundle config or TestState::enableDefaultProxyAutoRefresh()/disableDefaultProxyAutoRefresh().');

            $this->defaultProxyAutoRefresh = false;
        }

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

    public function setStoryManager(StoryManager $manager): self
    {
        $this->stories = $manager;

        return $this;
    }

    public function setModelFactoryManager(ModelFactoryManager $manager): self
    {
        $this->factories = $manager;

        return $this;
    }

    public function setFaker(Faker\Generator $faker): self
    {
        $this->faker = $faker;

        return $this;
    }

    public function enableDefaultProxyAutoRefresh(): self
    {
        $this->defaultProxyAutoRefresh = true;

        return $this;
    }

    public function disableDefaultProxyAutoRefresh(): self
    {
        $this->defaultProxyAutoRefresh = false;

        return $this;
    }

    public function isFlushingEnabled(): bool
    {
        return $this->flushEnabled;
    }

    public function disableDatabaseReset(): self
    {
        $this->databaseResetEnabled = false;

        return $this;
    }

    public function isDatabaseResetEnabled(): bool
    {
        return $this->databaseResetEnabled;
    }

    public function delayFlush(callable $callback): void
    {
        $this->flushEnabled = false;

        $callback();

        foreach ($this->managerRegistry()->getManagers() as $manager) {
            $manager->flush();
        }

        $this->flushEnabled = true;
    }

    /**
     * @param object|string $objectOrClass
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     * @template TObject of object
     * @psalm-param Proxy<TObject>|TObject|class-string<TObject> $objectOrClass
     * @psalm-return RepositoryProxy<TObject>
     */
    public function repositoryFor($objectOrClass): RepositoryProxy
    {
        if ($objectOrClass instanceof Proxy) {
            $objectOrClass = $objectOrClass->object();
        }

        if (!\is_string($objectOrClass)) {
            $objectOrClass = \get_class($objectOrClass);
        }

        return new RepositoryProxy($this->managerRegistry()->getRepository($objectOrClass));
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

    /** @psalm-assert !null $this->managerRegistry */
    public function hasManagerRegistry(): bool
    {
        return null !== $this->managerRegistry;
    }

    public function getOrmConnectionsToReset(): array
    {
        return $this->ormConnectionsToReset;
    }

    public function getOrmObjectManagersToReset(): array
    {
        return $this->ormObjectManagersToReset;
    }

    public function getOrmResetMode(): string
    {
        return $this->ormResetMode;
    }

    public function getOdmObjectManagersToReset(): array
    {
        return $this->odmObjectManagersToReset;
    }

    private function managerRegistry(): ManagerRegistry
    {
        if (!$this->hasManagerRegistry()) {
            /** @psalm-suppress MissingDependency */
            throw new \RuntimeException('Foundry was booted without doctrine. Ensure your TestCase extends '.KernelTestCase::class);
        }

        return $this->managerRegistry;
    }
}
