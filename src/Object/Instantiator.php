<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Object;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use function Symfony\Component\String\u;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @final
 *
 * @method static self withoutConstructor()
 */
class Instantiator
{
    private static ?PropertyAccessor $propertyAccessor = null;

    private bool $useConstructor = true;

    private bool $allowExtraAttributes = false;

    private array $extraAttributes = [];

    private bool $alwaysForceProperties = false;

    /** @var string[] */
    private array $forceProperties = [];

    public function __construct(bool $calledInternally = false)
    {
        if (!$calledInternally) {
            trigger_deprecation('zenstruck\foundry', '1.37.0', '%1$s constructor will be private in Foundry 2.0. Use either "%1$s::withConstructor()" or "%1$s::withoutConstructor()"', self::class);
        }
    }

    /**
     * @param class-string $class
     */
    public function __invoke(array $attributes, string $class): object
    {
        $object = $this->instantiate($class, $attributes);

        foreach ($attributes as $attribute => $value) {
            if (0 === \mb_strpos($attribute, 'optional:')) {
                trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using "optional:" attribute prefixes is deprecated, use Instantiator::allowExtra() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');
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
                trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using "force:" property prefixes is deprecated, use Instantiator::alwaysForce() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');

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

    public function __call(string $name, array $arguments): self
    {
        if ('withoutConstructor' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined method "%s::%s".', static::class, $name));
        }

        trigger_deprecation('zenstruck/foundry', '1.37.0', 'Calling instance method "%1$s::withoutConstructor()" is deprecated and will be removed in 2.0. Use static call instead: "%1$s::withoutConstructor()" instead.', static::class);

        $this->useConstructor = false;

        return $this;
    }

    public static function __callStatic(string $name, array $arguments): self
    {
        if ('withoutConstructor' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined method "%s::%s".', static::class, $name));
        }

        $instance = new self(calledInternally: true);

        $instance->useConstructor = false;

        return $instance;
    }

    public static function withConstructor(): self
    {
        return new self(calledInternally: true);
    }

    /**
     * Ignore attributes that can't be set to object.
     *
     * @param string[] $attributes The attributes you'd like the instantiator to ignore (if empty, ignore any extra)
     *
     * @deprecated Use self::allowExtra() instead
     */
    public function allowExtraAttributes(array $attributes = []): self
    {
        trigger_deprecation('zenstruck/foundry', '1.37.0', 'Method "Instantiator::allowExtraAttributes()" is deprecated. Please use "Instantiator::allowExtra()" instead.');

        return $this->allowExtra(...$attributes);
    }

    /**
     * Ignore attributes that can't be set to object.
     *
     * @param string $parameters The attributes you'd like the instantiator to ignore (if empty, ignore any extra)
     */
    public function allowExtra(string ...$parameters): self
    {
        if (empty($parameters)) {
            $this->allowExtraAttributes = true;
        }

        $this->extraAttributes = $parameters;

        return $this;
    }

    /**
     * Always force properties, never use setters (still uses constructor unless disabled).
     *
     * @param string[] $properties The properties you'd like the instantiator to "force set" (if empty, force set all)
     *
     * @deprecated Use self::alwaysForce() instead
     */
    public function alwaysForceProperties(array $properties = []): self
    {
        trigger_deprecation('zenstruck/foundry', '1.37.0', 'Method "Instantiator::alwaysForceProperties()" is deprecated. Please use "Instantiator::alwaysForce()" instead.');

        return $this->alwaysForce(...$properties);
    }

    /**
     * Always force properties, never use setters (still uses constructor unless disabled).
     *
     * @param string $properties The properties you'd like the instantiator to "force set" (if empty, force set all)
     */
    public function alwaysForce(string ...$properties): self
    {
        if (empty($properties)) {
            $this->alwaysForceProperties = true;
        }

        $this->forceProperties = $properties;

        return $this;
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
        return u($string)->camel()->toString();
    }

    private static function snake(string $string): string
    {
        return u($string)->snake()->toString();
    }

    /**
     * @param class-string $class
     */
    private function instantiate(string $class, array &$attributes): object
    {
        $class = new \ReflectionClass($class);
        $constructor = $class->getConstructor();

        if (!$this->useConstructor || !$constructor || !$constructor->isPublic()) {
            if ($this->useConstructor && $constructor && !$constructor->isPublic()) {
                trigger_deprecation('zenstruck\foundry', '1.37.0', 'Instantiator was created to instantiate "%s" by calling the constructor whereas the constructor is not public. This is deprecated and will throw an exception in Foundry 2.0. Use "%s::withoutConstructor()" instead or make constructor public.', $class->getName(), self::class);
            }

            return $class->newInstanceWithoutConstructor();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
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
                throw new \InvalidArgumentException(\sprintf('Missing constructor argument "%s" for "%s".', $parameter->getName(), $class->getName()));
            }

            // unset attribute so it isn't used when setting object properties
            unset($attributes[$name]);
        }

        return $class->newInstance(...$arguments);
    }
}

class_exists(\Zenstruck\Foundry\Instantiator::class);
