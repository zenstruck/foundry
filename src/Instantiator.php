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

use function Symfony\Component\String\u;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Instantiator
{
    private bool $withoutConstructor = false;
    private ?\ReflectionFunction $factory = null;
    private Hydrator $hydrator;
    private bool $selfHydrating = false;

    public function __construct()
    {
        $this->hydrator = new Hydrator();
    }

    /**
     * @param class-string $class
     */
    public function __invoke(array $attributes, string $class): object
    {
        // todo deprecation
        $object = $this->instantiate($class, $attributes);

        return ($this->hydrator)($object, $attributes);
    }

    /**
     * Instantiate objects using a callable.
     */
    public static function factory(callable $callback): self
    {
        $instantiator = new self();
        $instantiator->factory = new \ReflectionFunction($callback instanceof \Closure ? $callback : \Closure::fromCallable($callback));

        return $instantiator;
    }

    /**
     * Instantiate objects without calling the constructor.
     */
    public static function noConstructor(): self
    {
        return (new self())->withoutConstructor();
    }

    /**
     * Instantiate objects without calling the constructor.
     */
    public function withoutConstructor(): self
    {
        $clone = clone $this;
        $clone->withoutConstructor = true;

        return $clone;
    }

    /**
     * Ignore attributes that can't be set to object.
     *
     * @param string[] $attributes The attributes you'd like the instantiator to ignore (if empty, ignore any extra)
     */
    public function allowExtraAttributes(array $attributes = []): self
    {
        // todo deprecation
        $clone = clone $this;
        $clone->hydrator = $clone->hydrator->allowExtraAttributes($attributes);
        $clone->selfHydrating = true;

        return $clone;
    }

    /**
     * Always force properties, never use setters (still uses constructor unless disabled).
     *
     * @param string[] $properties The properties you'd like the instantiator to "force set" (if empty, force set all)
     */
    public function alwaysForceProperties(array $properties = []): self
    {
        // todo deprecation
        $clone = clone $this;
        $clone->hydrator = $clone->hydrator->alwaysForceProperties($properties);
        $clone->selfHydrating = true;

        return $clone;
    }

    /**
     * @throws \InvalidArgumentException if property does not exist for $object
     */
    public static function forceSet(object $object, string $property, mixed $value): void
    {
        // todo deprecation
        Hydrator::set($object, $property, $value);
    }

    /**
     * @throws \InvalidArgumentException if property does not exist for $object
     */
    public static function forceGet(object $object, string $property): mixed
    {
        // todo deprecation
        return Hydrator::get($object, $property);
    }

    /**
     * Check if parameter value was passed as the exact name - if not, try snake-cased, then kebab-cased versions.
     */
    private static function attributeNameForParameter(\ReflectionParameter $parameter, array $attributes): ?string
    {
        // try exact
        $name = $parameter->getName();

        if (\array_key_exists($name, $attributes)) {
            return $name;
        }

        // try snake case
        $name = self::snake($name);

        if (\array_key_exists($name, $attributes)) {
            trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using a differently cased attribute is deprecated, use the same case as the object property instead.');

            return $name;
        }

        // try kebab case
        $name = \str_replace('_', '-', $name);

        if (\array_key_exists($name, $attributes)) {
            trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using a differently cased attribute is deprecated, use the same case as the object property instead.');

            return $name;
        }

        return null;
    }

    private static function snake(string $string): string
    {
        return u($string)->snake();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function instantiate(string $class, array &$attributes = []): object
    {
        $class = new \ReflectionClass($class);

        if (!$function = $this->instantiatorFunction($class)) {
            return $class->newInstanceWithoutConstructor();
        }

        $arguments = [];

        foreach ($function->getParameters() as $parameter) {
            $name = self::attributeNameForParameter($parameter, $attributes);

            if ($name && \array_key_exists($name, $attributes)) {
                if ($parameter->isVariadic()) {
                    $arguments = \array_merge($arguments, $attributes[$name]);
                } else {
                    $arguments[] = $attributes[$name];
                }
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                throw new \InvalidArgumentException(\sprintf('Missing argument "%s" for "%s".', $parameter->getName(), $class->getName()));
            }

            // unset attribute so it isn't used when setting object properties
            unset($attributes[$name]);
        }

        if ($function instanceof \ReflectionMethod) {
            return $class->newInstance(...$arguments);
        }

        $object = $function->invoke(...$arguments);

        if (!$object instanceof $class->name) {
            throw new \LogicException(\sprintf('Instantiator factory must return "%s" but got "%s".', $class->name, \get_debug_type($object)));
        }

        return $object;
    }

    /**
     * @internal
     */
    public function isSelfHydrating(): bool
    {
        return $this->selfHydrating;
    }

    private function instantiatorFunction(\ReflectionClass $class): \ReflectionFunction|\ReflectionMethod|null
    {
        if ($this->factory) {
            return $this->factory;
        }

        $function = $class->getConstructor();

        if ($this->withoutConstructor || !$function || !$function->isPublic()) {
            return null;
        }

        return $function;
    }
}
