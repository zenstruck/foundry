<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Object;

use Zenstruck\Foundry\Factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 *
 * @phpstan-import-type Parameters from Factory
 */
final class Instantiator
{
    private const WITH_CONSTRUCTOR = '_with_constructor';
    private const WITHOUT_CONSTRUCTOR = '_without_constructor';

    private Hydrator $hydrator;
    private bool $hydration = true;

    private function __construct(private string|\Closure $mode)
    {
        $this->hydrator = new Hydrator();
    }

    /**
     * @template T of object
     *
     * @param Parameters      $parameters
     * @param class-string<T> $class
     *
     * @return T
     */
    public function __invoke(array $parameters, string $class): object
    {
        $object = $this->instantiate($parameters, $class);

        return $this->hydration ? ($this->hydrator)($object, $parameters) : $object;
    }

    public static function withConstructor(): self
    {
        return new self(self::WITH_CONSTRUCTOR);
    }

    public static function withoutConstructor(): self
    {
        return new self(self::WITHOUT_CONSTRUCTOR);
    }

    public static function namedConstructor(string $method): self
    {
        return new self($method);
    }

    public static function use(callable $factory): self
    {
        return new self($factory(...));
    }

    /**
     * Ignore attributes that can't be set to object.
     *
     * @param string ...$parameters The parameters you'd like the hydrator to ignore (if empty, ignore any extra)
     */
    public function allowExtra(string ...$parameters): self
    {
        $clone = clone $this;
        $clone->hydrator = $clone->hydrator->allowExtra(...$parameters);
        $clone->hydration = true;

        return $clone;
    }

    /**
     * Always force properties, never use setters (still uses constructor unless disabled).
     *
     * @param string ...$properties The properties you'd like the hydrator to "force set" (if empty, force set all)
     */
    public function alwaysForce(string ...$properties): self
    {
        $clone = clone $this;
        $clone->hydrator = $clone->hydrator->alwaysForce(...$properties);
        $clone->hydration = true;

        return $clone;
    }

    public function enableHydration(): self
    {
        $clone = clone $this;
        $clone->hydration = true;

        return $clone;
    }

    public function disableHydration(): self
    {
        $clone = clone $this;
        $clone->hydration = false;

        return $clone;
    }

    /**
     * @template T of object
     *
     * @param Parameters      $parameters
     * @param class-string<T> $class
     *
     * @return T
     */
    private function instantiate(array &$parameters, string $class): object
    {
        $refClass = new \ReflectionClass($class);

        if (self::WITHOUT_CONSTRUCTOR === $this->mode) {
            return $refClass->newInstanceWithoutConstructor();
        }

        if (!$factory = $this->factoryFor($refClass)) {
            return $refClass->newInstance();
        }

        $arguments = [];

        /** @var \ReflectionParameter $parameter */
        foreach ($factory->getParameters() as $parameter) {
            if (\array_key_exists($parameter->name, $parameters)) {
                if ($parameter->isVariadic()) {
                    $arguments = \array_merge($arguments, $parameters[$parameter->name]);
                } else {
                    $arguments[] = $parameters[$parameter->name];
                }

                unset($parameters[$parameter->name]);

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \LogicException(\sprintf('Missing required argument "%s" for "%s::%s()".', $parameter->name, $class, $factory->name));
        }

        if ($factory instanceof \ReflectionMethod && $factory->isConstructor()) {
            return $refClass->newInstance(...$arguments);
        }

        $object = $factory instanceof \ReflectionMethod ? $factory->invoke(null, ...$arguments) : $factory->invoke(...$arguments);

        if (!$object instanceof $class) {
            throw new \LogicException(\sprintf('Named constructor "%s" for "%s" must return an instance of "%s".', $factory->name, $class, $class));
        }

        return $object;
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private function factoryFor(\ReflectionClass $class): \ReflectionFunction|\ReflectionMethod|null
    {
        if ($this->mode instanceof \Closure) {
            return new \ReflectionFunction(($this->mode)(...));
        }

        if (self::WITH_CONSTRUCTOR === $this->mode) {
            return self::constructorFor($class);
        }

        return self::namedConstructorFor($class, $this->mode);
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private static function namedConstructorFor(\ReflectionClass $class, string $name): \ReflectionMethod
    {
        try {
            $method = $class->getMethod($name);
        } catch (\ReflectionException) {
            throw new \LogicException(\sprintf('Named constructor "%s" for "%s" does not exist.', $name, $class->getName()));
        }

        if (!$method->isPublic()) {
            throw new \LogicException(\sprintf('Named constructor "%s" for "%s" is not public.', $name, $class->getName()));
        }

        if (!$method->isStatic()) {
            throw new \LogicException(\sprintf('Named constructor "%s" for "%s" is not static.', $name, $class->getName()));
        }

        return $method;
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private static function constructorFor(\ReflectionClass $class): ?\ReflectionMethod
    {
        if (!$constructor = $class->getConstructor()) {
            return null;
        }

        if (!$constructor->isPublic()) {
            throw new \LogicException(\sprintf('Constructor for "%s" is not public.', $class->getName()));
        }

        return $constructor;
    }
}
