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

use Doctrine\Persistence\ManagerRegistry;
use Faker;
use Zenstruck\Foundry\Test\ORMDatabaseResetter;
use Zenstruck\Foundry\Exception\FoundryBootException;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Configuration
{
    private ?ManagerRegistry $managerRegistry = null;

    private StoryManager $stories;

    private \Faker\Generator $faker;

    /** @var callable */
    private $instantiator;

    private ?bool $defaultProxyAutoRefresh = null;

    private bool $databaseResetEnabled = true;

    /**
     * @param string[] $ormConnectionsToReset
     * @param string[] $ormObjectManagersToReset
     * @param string[] $odmObjectManagersToReset
     */
    public function __construct(private array $ormConnectionsToReset, private array $ormObjectManagersToReset, private string $ormResetMode, private array $odmObjectManagersToReset)
    {
        $this->stories = new StoryManager([]);
        $this->faker = Faker\Factory::create();
        $this->instantiator = new Instantiator();
    }

    public static function default(): self
    {
        return new self([], [], ORMDatabaseResetter::RESET_MODE_SCHEMA, []);
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

    public function disableDatabaseReset(): self
    {
        $this->databaseResetEnabled = false;

        return $this;
    }

    public function isDatabaseResetEnabled(): bool
    {
        return $this->databaseResetEnabled;
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
}
