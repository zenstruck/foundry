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
use Zenstruck\Foundry\Exception\FoundryNotBooted;
use Zenstruck\Foundry\Exception\PersistenceDisabled;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
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
     * @var InstantiatorCallable
     */
    public $instantiator;

    /** @var \Closure():self|self|null */
    private static \Closure|self|null $instance = null;

    /**
     * @param InstantiatorCallable $instantiator
     */
    public function __construct(
        public readonly FactoryRegistry $factories,
        public readonly Faker\Generator $faker,
        callable $instantiator,
        public readonly StoryRegistry $stories,
        private readonly ?PersistenceManager $persistence = null,
    ) {
        $this->instantiator = $instantiator;
    }

    public function persistence(): PersistenceManager
    {
        return $this->persistence ?? throw new PersistenceNotAvailable('No persistence managers configured. Note: persistence cannot be used in unit tests.');
    }

    public function isPersistenceAvailable(): bool
    {
        return (bool) $this->persistence;
    }

    public function assertPersistanceEnabled(): void
    {
        if (!$this->isPersistenceAvailable() || !$this->persistence()->isEnabled()) {
            throw new PersistenceDisabled('Cannot get repository when persist is disabled.');
        }
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            throw new FoundryNotBooted('Foundry is not yet booted. Ensure ZenstruckFoundryBundle is enabled. If in a test, ensure your TestCase has the Factories trait.');
        }

        return \is_callable(self::$instance) ? (self::$instance)() : self::$instance;
    }

    public static function isBooted(): bool
    {
        return null !== self::$instance;
    }

    public static function boot(\Closure|self $configuration): void
    {
        self::$instance = $configuration;
    }

    public static function shutdown(): void
    {
        StoryRegistry::reset();
        self::$instance = null;
    }
}
