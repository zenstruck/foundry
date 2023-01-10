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

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use function Symfony\Component\String\u;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Instantiator
{
    private static ?PropertyAccessor $propertyAccessor = null;

    private bool $withoutConstructor = false;

    private bool $allowExtraAttributes = false;

    private array $extraAttributes = [];

    private bool $alwaysForceProperties = false;

    /** @var string[] */
    private array $forceProperties = [];

    private ?\ReflectionFunction $factory = null;

    /**
     * @param class-string $class
     */
    public function __invoke(array $attributes, string $class): object
    {
        $object = $this->instantiate($class, $attributes);

        foreach ($attributes as $attribute => $value) {
            if (0 === \mb_strpos($attribute, 'optional:')) {
                trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using "optional:" attribute prefixes is deprecated, use Instantiator::allowExtraAttributes() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');
                continue;
            }

            if (\in_array($attribute, $this->extraAttributes, true)) {
                continue;
            }

            if ($this->alwaysForceProperties || \in_array($attribute, $this->forceProperties, true)) {
                try {
                    self::forceSet($object, $attribute, $value);
                } catch (\InvalidArgumentException $e) {
                    if (!$this->allowExtraAttributes) {
                        throw $e;
                    }
                }

                continue;
            }

            if (0 === \mb_strpos($attribute, 'force:')) {
                trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using "force:" property prefixes is deprecated, use Instantiator::alwaysForceProperties() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');

                self::forceSet($object, \mb_substr($attribute, 6), $value);

                continue;
            }

            try {
                self::propertyAccessor()->setValue($object, $attribute, $value);
            } catch (NoSuchPropertyException $e) {
                // see if attribute was snake/kebab cased
                try {
                    self::propertyAccessor()->setValue($object, self::camel($attribute), $value);
                    trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using a differently cased attribute is deprecated, use the same case as the object property instead.');
                } catch (NoSuchPropertyException $e) {
                    if (!$this->allowExtraAttributes) {
                        throw new \InvalidArgumentException(\sprintf('Cannot set attribute "%s" for object "%s" (not public and no setter).', $attribute, $class), 0, $e);
                    }
                }
            }
        }

        return $object;
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
        $clone = clone $this;

        if (empty($attributes)) {
            $clone->allowExtraAttributes = true;
        }

        $clone->extraAttributes = $attributes;

        return $clone;
    }

    /**
     * Always force properties, never use setters (still uses constructor unless disabled).
     *
     * @param string[] $properties The properties you'd like the instantiator to "force set" (if empty, force set all)
     */
    public function alwaysForceProperties(array $properties = []): self
    {
        $clone = clone $this;

        if (empty($properties)) {
            $clone->alwaysForceProperties = true;
        }

        $clone->forceProperties = $properties;

        return $clone;
    }

    /**
     * @throws \InvalidArgumentException if property does not exist for $object
     */
    public static function forceSet(object $object, string $property, mixed $value): void
    {
        self::accessibleProperty($object, $property)->setValue($object, $value);
    }

    /**
     * @return mixed
     */
    public static function forceGet(object $object, string $property)
    {
        return self::accessibleProperty($object, $property)->getValue($object);
    }

    private static function propertyAccessor(): PropertyAccessor
    {
        return self::$propertyAccessor ?: self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    private static function accessibleProperty(object $object, string $name): \ReflectionProperty
    {
        $class = new \ReflectionClass($object);

        // try fetching first by exact name, if not found, try camel-case
        $property = self::reflectionProperty($class, $name);

        if (!$property && $property = self::reflectionProperty($class, self::camel($name))) {
            trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using a differently cased attribute is deprecated, use the same case as the object property instead.');
        }

        if (!$property) {
            throw new \InvalidArgumentException(\sprintf('Class "%s" does not have property "%s".', $class->getName(), $name));
        }

        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }

        return $property;
    }

    private static function reflectionProperty(\ReflectionClass $class, string $name): ?\ReflectionProperty
    {
        try {
            return $class->getProperty($name);
        } catch (\ReflectionException) {
            if ($class = $class->getParentClass()) {
                return self::reflectionProperty($class, $name);
            }
        }

        return null;
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

    private static function camel(string $string): string
    {
        return u($string)->camel();
    }

    private static function snake(string $string): string
    {
        return u($string)->snake();
    }

    /**
     * @param class-string $class
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

        if (!\is_a($object, $class->name, true)) {
            throw new \LogicException(\sprintf('Instantiator factory must return "%s" but got "%s".', $class->name, \get_debug_type($object)));
        }

        return $object;
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
