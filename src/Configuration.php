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

use Faker;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zenstruck\Foundry\Exception\FactoriesTraitNotUsed;
use Zenstruck\Foundry\Exception\FoundryNotBooted;
use Zenstruck\Foundry\Exception\PersistenceDisabled;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\InMemory\CannotEnableInMemory;
use Zenstruck\Foundry\InMemory\InMemoryRepositoryRegistry;
use Zenstruck\Foundry\Persistence\PersistedObjectsTracker;
use Zenstruck\Foundry\Persistence\PersistenceManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type InstantiatorCallable from ObjectFactory
 */
final class Configuration
{
    /**
     * @readonly
     *
     * @phpstan-var InstantiatorCallable
     */
    public $instantiator;

    /**
     * This property is only filled if the PHPUnit extension is used!
     */
    private bool $bootedForDataProvider = false;

    /** @var \Closure():self|self|null */
    private static \Closure|self|null $instance = null;

    private bool $inMemory = false;

    /**
     * @phpstan-param InstantiatorCallable $instantiator
     */
    public function __construct(
        public readonly FactoryRegistryInterface $factories,
        public readonly FakerAdapter $fakerAdapter,
        callable $instantiator,
        public readonly StoryRegistry $stories,
        private readonly ?PersistenceManager $persistence = null,
        public readonly bool $flushOnce = false,
        public readonly ?InMemoryRepositoryRegistry $inMemoryRepositoryRegistry = null,
        public readonly ?PersistedObjectsTracker $persistedObjectsTracker = null,
        private readonly bool $enableAutoRefreshWithLazyObjects = false,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->instantiator = $instantiator;
    }

    public function faker(): Faker\Generator
    {
        return $this->fakerAdapter->faker();
    }

    /**
     * @throws PersistenceNotAvailable
     */
    public function persistence(): PersistenceManager
    {
        return $this->persistence ?? throw new PersistenceNotAvailable('No persistence managers configured. Note: persistence cannot be used in unit tests.');
    }

    public function isPersistenceAvailable(): bool
    {
        return (bool) $this->persistence;
    }

    public function isPersistenceEnabled(): bool
    {
        return $this->isPersistenceAvailable() && $this->persistence()->isEnabled();
    }

    public function assertPersistenceEnabled(): void
    {
        if (!$this->isPersistenceEnabled()) {
            throw new PersistenceDisabled('Cannot get repository when persist is disabled (if in a unit test, you probably should not try to get the repository).');
        }
    }

    public function hasEventDispatcher(): bool
    {
        return (bool) $this->eventDispatcher;
    }

    public function eventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher ?? throw new \RuntimeException('No event dispatcher configured.');
    }

    public function inADataProvider(): bool
    {
        return $this->bootedForDataProvider;
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            throw new FoundryNotBooted();
        }

        FactoriesTraitNotUsed::throwIfComingFromKernelTestCaseWithoutFactoriesTrait();

        return \is_callable(self::$instance) ? (self::$instance)() : self::$instance;
    }

    public static function isBooted(): bool
    {
        return null !== self::$instance;
    }

    /** @param \Closure():self|self $configuration */
    public static function boot(\Closure|self $configuration): void
    {
        self::$instance = $configuration;
    }

    /** @param \Closure():self|self $configuration */
    public static function bootForDataProvider(\Closure|self $configuration): void
    {
        self::$instance = \is_callable($configuration) ? ($configuration)() : $configuration;
        self::$instance->bootedForDataProvider = true;
    }

    /**
     * /!\ Until PHPUnit 9 support is not dropped, this method MUST NOT call Configuration::instance()
     * Otherwise, it will reboot the kernel, leading to complex bugs.
     */
    public static function shutdown(): void
    {
        PersistedObjectsTracker::reset();
        StoryRegistry::reset();
        FakerAdapter::reset();
        self::$instance = null;
    }

    /**
     * @throws CannotEnableInMemory
     */
    public function enableInMemory(): void
    {
        if (null === $this->inMemoryRepositoryRegistry) {
            throw CannotEnableInMemory::noInMemoryRepositoryRegistry();
        }

        $this->inMemory = true;
    }

    /**
     * @phpstan-assert-if-true InMemoryRepositoryRegistry $this->inMemoryRepositoryRegistry
     */
    public function isInMemoryEnabled(): bool
    {
        return $this->inMemory;
    }

    public static function autoRefreshWithLazyObjectsIsEnabled(): bool
    {
        return self::isBooted() && self::instance()->enableAutoRefreshWithLazyObjects;
    }

    public static function triggerProxyDeprecation(?string $additionalMessage = null): void
    {
        if (\PHP_VERSION_ID < 80400) {
            return;
        }

        if (!\trait_exists(\Symfony\Component\VarExporter\LazyProxyTrait::class)) {
            // Deprecation is not needed: PersistentProxyObjectFactory will actually throw when create() is called.
            return;
        }

        $message = <<<DEPRECATION
            Proxy usage is deprecated in PHP 8.4. You should extend directly PersistentObjectFactory in your factories.
            Foundry now leverages the native PHP lazy system to auto-refresh objects (it can be enabled with "zenstruck_foundry.enable_auto_refresh_with_lazy_objects" configuration).
            See https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.7.md to upgrade.
            DEPRECATION;

        if ($additionalMessage) {
            $message = "{$additionalMessage}\n{$message}";
        }

        trigger_deprecation('zenstruck/foundry', '2.7', $message);
    }
}
