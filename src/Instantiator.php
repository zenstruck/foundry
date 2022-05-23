<?php

namespace Zenstruck\Foundry;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Instantiator
{
    /** @var PropertyAccessor|null */
    private static $propertyAccessor;

    /** @var bool */
    private $withoutConstructor = false;

    /** @var bool */
    private $allowExtraAttributes = false;

    /** @var array */
    private $extraAttributes = [];

    /** @var bool */
    private $alwaysForceProperties = false;

    /** @var string[] */
    private $forceProperties = [];

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
     * Instantiate objects without calling the constructor.
     */
    public function withoutConstructor(): self
    {
        $this->withoutConstructor = true;

        return $this;
    }

    /**
     * Ignore attributes that can't be set to object.
     *
     * @param string[] $attributes The attributes you'd like the instantiator to ignore (if empty, ignore any extra)
     */
    public function allowExtraAttributes(array $attributes = []): self
    {
        if (empty($attributes)) {
            $this->allowExtraAttributes = true;
        }

        $this->extraAttributes = $attributes;

        return $this;
    }

    /**
     * Always force properties, never use setters (still uses constructor unless disabled).
     *
     * @param string[] $properties The properties you'd like the instantiator to "force set" (if empty, force set all)
     */
    public function alwaysForceProperties(array $properties = []): self
    {
        if (empty($properties)) {
            $this->alwaysForceProperties = true;
        }

        $this->forceProperties = $properties;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @throws \InvalidArgumentException if property does not exist for $object
     */
    public static function forceSet(object $object, string $property, $value): void
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
        } catch (\ReflectionException $e) {
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

    /**
     * @see https://github.com/symfony/symfony/blob/a73523b065221b6b93cd45bf1cc7c59e7eb2dcdf/src/Symfony/Component/String/AbstractUnicodeString.php#L156
     *
     * @todo use Symfony/String once stable
     */
    private static function camel(string $string): string
    {
        return \str_replace(' ', '', \preg_replace_callback('/\b./u', static function($m) use (&$i): string {
            return 1 === ++$i ? ('İ' === $m[0] ? 'i̇' : \mb_strtolower($m[0], 'UTF-8')) : \mb_convert_case($m[0], \MB_CASE_TITLE, 'UTF-8');
        }, \preg_replace('/[^\pL0-9]++/u', ' ', $string)));
    }

    /**
     * @see https://github.com/symfony/symfony/blob/a73523b065221b6b93cd45bf1cc7c59e7eb2dcdf/src/Symfony/Component/String/AbstractUnicodeString.php#L361
     *
     * @todo use Symfony/String once stable
     */
    private static function snake(string $string): string
    {
        $string = self::camel($string);

        /**
         * @see https://github.com/symfony/symfony/blob/a73523b065221b6b93cd45bf1cc7c59e7eb2dcdf/src/Symfony/Component/String/AbstractUnicodeString.php#L369
         */
        $string = \preg_replace_callback('/\b./u', static function(array $m): string {
            return \mb_convert_case($m[0], \MB_CASE_TITLE, 'UTF-8');
        }, $string, 1);

        return \mb_strtolower(\preg_replace(['/(\p{Lu}+)(\p{Lu}\p{Ll})/u', '/([\p{Ll}0-9])(\p{Lu})/u'], '\1_\2', $string), 'UTF-8');
    }

    private function instantiate(string $class, array &$attributes): object
    {
        $class = new \ReflectionClass($class);
        $constructor = $class->getConstructor();

        if ($this->withoutConstructor || !$constructor || !$constructor->isPublic()) {
            return $class->newInstanceWithoutConstructor();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = self::attributeNameForParameter($parameter, $attributes);

            if (\array_key_exists($name, $attributes)) {
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
