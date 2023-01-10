<?php

namespace Zenstruck\Foundry;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use function Symfony\Component\String\u;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Hydrator
{
    public const ALLOW_EXTRA_ATTRIBUTES = 1;
    public const ALWAYS_FORCE_PROPERTIES = 2;

    private static PropertyAccessor $defaultAccessor;

    private PropertyAccessorInterface $accessor;

    private bool $allowExtraAttributes = false;

    /** @var string[] */
    private array $extraAttributes = [];

    private bool $alwaysForceProperties = false;

    /** @var string[] */
    private array $forceProperties = [];

    private ?\Closure $callback = null;

    /**
     * @internal
     */
    public function __construct(?PropertyAccessorInterface $accessor = null)
    {
        $this->accessor = $accessor ?? self::$defaultAccessor ??= new PropertyAccessor();
    }

    public function __invoke(object $object, array $attributes): object
    {
        if ($this->callback) {
            return ($this->callback)($object, $attributes);
        }

        foreach ($attributes as $attribute => $value) {
            if (\str_starts_with($attribute, 'optional:')) {
                trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using "optional:" attribute prefixes is deprecated, use Instantiator::allowExtraAttributes() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');
                continue;
            }

            if (\in_array($attribute, $this->extraAttributes, true)) {
                continue;
            }

            if ($this->alwaysForceProperties || \in_array($attribute, $this->forceProperties, true)) {
                try {
                    self::set($object, $attribute, $value);
                } catch (\InvalidArgumentException $e) {
                    if (!$this->allowExtraAttributes) {
                        throw $e;
                    }
                }

                continue;
            }

            if (\str_starts_with($attribute, 'force:')) {
                trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using "force:" property prefixes is deprecated, use Instantiator::alwaysForceProperties() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');

                self::set($object, \mb_substr($attribute, 6), $value);

                continue;
            }

            try {
                $this->accessor->setValue($object, $attribute, $value);
            } catch (NoSuchPropertyException $e) {
                // see if attribute was snake/kebab cased
                try {
                    $this->accessor->setValue($object, self::camel($attribute), $value);
                    trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using a differently cased attribute is deprecated, use the same case as the object property instead.');
                } catch (NoSuchPropertyException $e) {
                    if (!$this->allowExtraAttributes) {
                        throw new \InvalidArgumentException(\sprintf('Cannot set attribute "%s" for object "%s" (not public and no setter).', $attribute, $object::class), 0, $e);
                    }
                }
            }
        }

        return $object;
    }

    /**
     * @throws \InvalidArgumentException if property does not exist for $object
     */
    public static function set(object $object, string $property, mixed $value): void
    {
        self::accessibleProperty($object, $property)->setValue($object, $value);
    }

    /**
     * @throws \InvalidArgumentException if property does not exist for $object
     */
    public static function get(object $object, string $property): mixed
    {
        return self::accessibleProperty($object, $property)->getValue($object);
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
     * @param callable(object,array):object $callback
     */
    public function using(callable $callback): self
    {
        $clone = clone $this;
        $clone->callback = $callback instanceof \Closure ? $callback : \Closure::fromCallable($callback);

        return $clone;
    }

    /**
     * @param int-mask-of<self::*> $mode
     */
    public function withMode(int $mode): self
    {
        $clone = clone $this;

        if ($mode & self::ALLOW_EXTRA_ATTRIBUTES) {
            $clone = $clone->allowExtraAttributes();
        }

        if ($mode & self::ALWAYS_FORCE_PROPERTIES) {
            $clone = $clone->alwaysForceProperties();
        }

        return $clone;
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

    private static function camel(string $string): string
    {
        return u($string)->camel();
    }
}
