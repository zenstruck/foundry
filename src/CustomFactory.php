<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ObjectRepository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class CustomFactory extends Factory
{
    private function __construct()
    {
        parent::__construct(static::getClass());
    }

    /**
     * @param array|callable|string $defaultAttributes If string, assumes state
     * @param string                ...$states         Optionally pass default states (these must be methods on your ObjectFactory with no arguments)
     */
    final public static function new($defaultAttributes = [], string ...$states): self
    {
        // todo - is this too magical?
        if (\is_string($defaultAttributes)) {
            $states = \array_merge([$defaultAttributes], $states);
            $defaultAttributes = [];
        }

        $factory = new static();
        $factory = $factory
            ->withAttributes([$factory, 'getDefaults'])
            ->withAttributes($defaultAttributes)
            ->initialize()
        ;

        foreach ($states as $state) {
            $factory = $factory->{$state}();
        }

        return $factory;
    }

    /**
     * Instantiate and persist.
     *
     * @param array|callable $attributes
     *
     * @return Proxy|object
     */
    final public static function create($attributes = [], ?bool $proxy = null): object
    {
        return static::new()->persist($attributes, $proxy);
    }

    /**
     * Instantiate many and persist.
     *
     * @param array|callable $attributes
     *
     * @return Proxy[]|object[]
     */
    final public static function createMany(int $number, $attributes = [], ?bool $proxy = null): array
    {
        return static::new()->persistMany($number, $attributes, $proxy);
    }

    /**
     * Instantiate without persisting.
     *
     * @param array|callable $attributes
     */
    final public static function make($attributes = []): object
    {
        return static::new()->instantiate($attributes);
    }

    /**
     * Instantiate many without persisting.
     *
     * @param array|callable $attributes
     *
     * @return object[]
     */
    final public static function makeMany(int $number, $attributes = []): array
    {
        return static::new()->instantiateMany($number, $attributes);
    }

    /**
     * Try and find existing object for the given $attributes. If not found,
     * instantiate and persist.
     *
     * @return Proxy|object
     */
    final public static function findOrCreate(array $attributes): object
    {
        if ($found = self::repository(true)->find($attributes)) {
            return $found;
        }

        return self::create($attributes);
    }

    /**
     * @return RepositoryProxy|ObjectRepository
     */
    final public static function repository(bool $proxy = true): ObjectRepository
    {
        return PersistenceManager::repositoryFor(static::getClass(), $proxy);
    }

    /**
     * Override to add default instantiator and default afterInstantiate/afterPersist events.
     */
    protected function initialize(): self
    {
        return $this;
    }

    /**
     * @param array|callable $attributes
     */
    final protected function addState($attributes = []): self
    {
        return $this->withAttributes($attributes);
    }

    abstract protected static function getClass(): string;

    abstract protected function getDefaults(): array;
}
