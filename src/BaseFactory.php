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
use Zenstruck\Foundry\Exception\FoundryBootException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 *
 * @template T
 *
 * @method static list<T> createMany(int $number, Attributes $attributes = [])
 *
 * @phpstan-type Parameters array<string,mixed>
 * @phpstan-type Attributes Parameters|(callable():Parameters)
 * @phpstan-type SequenceAttributes iterable<Parameters>|(callable(): iterable<Parameters>)
 */
abstract class BaseFactory
{
    private static FactoryManager|null $factoryManager = null;
    private static Configuration|null $configuration = null;

    /** @var array<callable():Parameters> */
    private array $attributes = [];

    // let's keep an empty constructor for BC
    public function __construct()
    {
    }

    public function __call(string $name, array $arguments): array
    {
        if ('createMany' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined method "%s::%s".', static::class, $name));
        }

        trigger_deprecation('zenstruck/foundry', '1.7', 'Calling instance method "%1$s::createMany()" is deprecated and will be removed in 2.0, use e.g. "%1$s::new()->many(%2$d)->create()" instead.', static::class, $arguments[0]);

        return $this->many($arguments[0])->create($arguments[1] ?? []);
    }

    public static function __callStatic(string $name, array $arguments): array
    {
        if ('createMany' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined static method "%s::%s".', static::class, $name));
        }

        return static::new()->many($arguments[0])->create($arguments[1] ?? []);
    }

    /**
     * @param Attributes|string $attributes
     */
    final public static function new(array|callable|string $attributes = [], string ...$states): static
    {
        if (\is_string($attributes) || $states) {
            trigger_deprecation('zenstruck/foundry', '1.32', 'Passing states as strings is deprecated and this behavior will be removed in 2.0.', self::class, self::class);
        }

        if (\is_string($attributes)) {
            $states = [$attributes, ...$states];
            $attributes = [];
        }

        try {
            $factory = self::isBooted() ? static::factoryManager()->create(static::class) : new static(); // @phpstan-ignore-line
        } catch (\ArgumentCountError $e) {
            throw new \RuntimeException('Factories with dependencies (Factory services) cannot be created before foundry is booted.', 0, $e);
        }

        $factory = $factory
            ->withAttributes($attributes)
            ->initialize()
        ;

        if (!$factory instanceof static) {
            throw new \TypeError(\sprintf('"%1$s::initialize()" must return an instance of "%1$s".', static::class));
        }

        foreach ($states as $state) {
            if (!\method_exists($factory, $state)) {
                throw new \InvalidArgumentException(\sprintf('"%s" is not a valid state for "%s".', $state, static::class));
            }

            $factory = $factory->{$state}();
        }

        return $factory;
    }

    /**
     * @param Attributes $attributes
     *
     * @return T
     */
    final public static function createOne(array|callable $attributes = []): mixed
    {
        return static::new($attributes)->create();
    }

    /**
     * @param Attributes $attributes
     *
     * @return T
     */
    abstract public function create(array|callable $attributes = []): mixed;

    /**
     * @param int|null $max If set, when created, the collection will be a random size between $min and $max
     *
     * @return FactoryCollection<T>
     */
    final public function many(int $min, ?int $max = null): FactoryCollection
    {
        if (!$max) {
            return FactoryCollection::set($this, $min);
        }

        return FactoryCollection::range($this, $min, $max);
    }

    /**
     * @param SequenceAttributes $sequence
     *
     * @return FactoryCollection<T>
     */
    final public function sequence(iterable|callable $sequence): FactoryCollection
    {
        if (\is_callable($sequence)) {
            $sequence = $sequence();
        }

        return FactoryCollection::sequence($this, $sequence);
    }

    /**
     * A shortcut to create multiple models, based on a sequence, without states.
     *
     * @param SequenceAttributes $sequence
     *
     * @return list<T>
     */
    final public static function createSequence(iterable|callable $sequence): array
    {
        return static::new()->sequence($sequence)->create();
    }

    /**
     * @param Attributes $attributes
     */
    final public function withAttributes(array|callable $attributes): static
    {
        if (!$attributes) {
            return $this;
        }

        $clone = clone $this;
        $clone->attributes[] = \is_callable($attributes) ? $attributes : static fn() => $attributes;

        return $clone;
    }

    /**
     * @internal
     */
    final public static function boot(FactoryManager $factoryManager, Configuration $configuration): void
    {
        self::$factoryManager = $factoryManager;
        self::$configuration = $configuration;
    }

    /**
     * @internal
     */
    final public static function isBooted(): bool
    {
        return null !== self::$factoryManager || null !== self::$configuration;
    }

    /**
     * @internal
     */
    final public static function shutdown(): void
    {
        if (!self::isBooted()) {
            return;
        }

        self::factoryManager()->faker()->unique(true); // reset unique
        self::$factoryManager = null;
        self::$configuration = null;
    }

    /**
     * @internal
     * @throws FoundryBootException
     */
    final public static function factoryManager(): FactoryManager
    {
        if (!self::isBooted()) {
            throw FoundryBootException::notBootedYet(self::class);
        }

        return self::$factoryManager; // @phpstan-ignore-line
    }

    /**
     * @internal
     * @throws FoundryBootException
     */
    final public static function configuration(): Configuration
    {
        if (!self::isBooted()) {
            throw FoundryBootException::notBootedYet(self::class);
        }

        return self::$configuration; // @phpstan-ignore-line
    }

    final public static function faker(): Faker\Generator
    {
        try {
            return self::factoryManager()->faker();
        } catch (FoundryBootException $exception) {
            throw new \RuntimeException("Cannot get Foundry's configuration. If using faker in a data provider, consider passing attributes as a callable.", previous: $exception);
        }
    }

    /**
     * @internal
     */
    protected function normalizeAttribute(mixed $value, string $name): mixed
    {
        if (\is_callable($value)) {
            $value = $value();
        }

        if ($value instanceof FactoryCollection) {
            $value = $value->all();
        }

        if (\is_array($value)) {
            return \array_map(
                fn($value) => $this->normalizeAttribute($value, $name),
                $value
            );
        }

        if (!$value instanceof self) {
            return $value;
        }

        return $value->create() instanceof Proxy ? $value->create()->object() : $value->create();
    }

    /**
     * @param Attributes $attributes
     */
    final protected function mergedAttributes(array|callable $attributes): array
    {
        return \array_merge(
            ...[
                $this->getDefaults(),
                ...\array_map(static fn(callable $attributes) => $attributes(), $this->attributes),
                \is_callable($attributes) ? $attributes() : $attributes,
            ]
        );
    }

    /**
     * @return static
     */
    #[\ReturnTypeWillChange]
    protected function initialize()
    {
        return $this;
    }

    /**
     * @param Attributes $attributes
     */
    final protected function addState(array|callable $attributes = []): static
    {
        return $this->withAttributes($attributes);
    }

    /**
     * @return Parameters
     */
    abstract protected function getDefaults(): array;
}
