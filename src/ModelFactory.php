<?php

namespace Zenstruck\Foundry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ModelFactory extends Factory
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

        if (!$factory instanceof static) {
            throw new \TypeError(\sprintf('"%1$s::initialize()" must return an instance of "%1$s".', static::class));
        }

        foreach ($states as $state) {
            $factory = $factory->{$state}();
        }

        return $factory;
    }

    /**
     * Try and find existing object for the given $attributes. If not found,
     * instantiate and persist.
     *
     * @return Proxy|object
     */
    final public static function findOrCreate(array $attributes): Proxy
    {
        if ($found = static::repository()->find($attributes)) {
            return $found;
        }

        return static::new()->create($attributes);
    }

    /**
     * @see RepositoryProxy::random()
     */
    final public static function random(): Proxy
    {
        return static::repository()->random();
    }

    /**
     * @see RepositoryProxy::randomSet()
     */
    final public static function randomSet(int $number): array
    {
        return static::repository()->randomSet($number);
    }

    /**
     * @see RepositoryProxy::randomRange()
     */
    final public static function randomRange(int $min, int $max): array
    {
        return static::repository()->randomRange($min, $max);
    }

    final public static function repository(): RepositoryProxy
    {
        return static::configuration()->repositoryFor(static::getClass());
    }

    /**
     * Override to add default instantiator and default afterInstantiate/afterPersist events.
     *
     * @return static
     */
    protected function initialize()
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
