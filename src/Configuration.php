<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Doctrine\ORM\EntityRepository;
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
    private ?ManagerRegistry $managerRegistry = null;

    private StoryManager $stories;

    private ModelFactoryManager $factories;

    private \Faker\Generator $faker;

    /** @var callable */
    private $instantiator;

    private ?bool $defaultProxyAutoRefresh = null;

    private bool $flushEnabled = true;

    private bool $databaseResetEnabled = true;

    /**
     * @param string[] $ormConnectionsToReset
     * @param string[] $ormObjectManagersToReset
     * @param string[] $odmObjectManagersToReset
     */
    public function __construct(private array $ormConnectionsToReset, private array $ormObjectManagersToReset, private string $ormResetMode, private array $odmObjectManagersToReset)
    {
        $this->stories = new StoryManager([]);
        $this->factories = new ModelFactoryManager([]);
        $this->faker = Faker\Factory::create();
        $this->instantiator = new Instantiator();
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

        foreach ($this->managerRegistry()?->getManagers() ?? [] as $manager) {
            $manager->flush();
        }

        $this->flushEnabled = true;
    }

    /**
     * @template TObject of object
     * @phpstan-param Proxy<TObject>|TObject|class-string<TObject> $objectOrClass
     * @phpstan-return RepositoryProxy<TObject>
     */
    public function repositoryFor(object|string $objectOrClass): RepositoryProxy
    {
        if ($objectOrClass instanceof Proxy) {
            $objectOrClass = $objectOrClass->object();
        }

        if (!\is_string($objectOrClass)) {
            $objectOrClass = $objectOrClass::class;
        }

        /** @var EntityRepository<TObject>|null $repository */
        $repository = $this->managerRegistry()?->getRepository($objectOrClass);

        if (!$repository) {
            throw new \RuntimeException(\sprintf('No repository registered for "%s".', $objectOrClass));
        }

        return new RepositoryProxy($repository);
    }

    public function objectManagerFor(object|string $objectOrClass): ObjectManager
    {
        $class = \is_string($objectOrClass) ? $objectOrClass : $objectOrClass::class;

        if (!$objectManager = $this->managerRegistry()?->getManagerForClass($class)) {
            throw new \RuntimeException(\sprintf('No object manager registered for "%s".', $class));
        }

        return $objectManager;
    }

    /** @phpstan-assert !null $this->managerRegistry */
    public function hasManagerRegistry(): bool
    {
        return null !== $this->managerRegistry;
    }

    /**
     * @return string[]
     */
    public function getOrmConnectionsToReset(): array
    {
        return $this->ormConnectionsToReset;
    }

    /**
     * @return string[]
     */
    public function getOrmObjectManagersToReset(): array
    {
        return $this->ormObjectManagersToReset;
    }

    public function getOrmResetMode(): string
    {
        return $this->ormResetMode;
    }

    /**
     * @return string[]
     */
    public function getOdmObjectManagersToReset(): array
    {
        return $this->odmObjectManagersToReset;
    }

    private function managerRegistry(): ?ManagerRegistry
    {
        if (!$this->hasManagerRegistry()) {
            throw new \RuntimeException('Foundry was booted without doctrine. Ensure your TestCase extends '.KernelTestCase::class);
        }

        return $this->managerRegistry;
    }
}
