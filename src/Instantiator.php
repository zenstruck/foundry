<?php

namespace Zenstruck\Foundry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Instantiator
{
    private const MODE_CONSTRUCTOR_AND_PROPERTIES = 1;
    private const MODE_ONLY_CONSTRUCTOR = 2;
    private const MODE_ONLY_PROPERTIES = 3;

    private int $mode;
    private bool $strict = false;

    private function __construct(int $mode)
    {
        $this->mode = $mode;
    }

    public function __invoke(array $attributes, string $class): object
    {
        $object = $this->instantiate($class, $attributes);

        if (self::MODE_ONLY_CONSTRUCTOR === $this->mode) {
            return $object;
        }

        foreach ($attributes as $name => $value) {
            $this->forceSet($object, $name, $value);
        }

        return $object;
    }

    /**
     * This mode instantiates the object with the given attributes as constructor arguments, then
     * sets the remaining attributes to properties (public and private).
     */
    public static function default(): self
    {
        return new self(self::MODE_CONSTRUCTOR_AND_PROPERTIES);
    }

    /**
     * This mode only instantiates the object with the given attributes as constructor arguments.
     */
    public static function onlyConstructor(): self
    {
        return new self(self::MODE_ONLY_CONSTRUCTOR);
    }

    /**
     * This mode instantiates the object without calling the constructor, then sets the attributes to
     * properties (public and private).
     */
    public static function withoutConstructor(): self
    {
        return new self(self::MODE_ONLY_PROPERTIES);
    }

    /**
     * Throws \InvalidArgumentException for attributes passed that don't exist.
     */
    public function strict(): self
    {
        $this->strict = true;

        return $this;
    }

    public function forceSet(object $object, string $property, $value): void
    {
        $property = $this->propertyForAttributeName($object, $property);

        if (!$property) {
            return;
        }

        $property->setValue($object, $value);
    }

    /**
     * @return mixed|null
     *
     * @throws \InvalidArgumentException if $strict = true
     */
    public function forceGet(object $object, string $property)
    {
        $property = $this->propertyForAttributeName($object, $property);

        return $property ? $property->getValue($object) : null;
    }

    /**
     * Check if property exists for passed $name - if not, try camel-casing the name.
     */
    private function propertyForAttributeName(object $object, string $name): ?\ReflectionProperty
    {
        $class = new \ReflectionClass($object);

        // try fetching first by exact name, if not found, try camel-case
        if (!$property = self::getReflectionProperty($class, $name)) {
            $property = self::getReflectionProperty($class, self::camel($name));
        }

        if (!$property && $this->strict) {
            throw new \InvalidArgumentException(\sprintf('Class "%s" does not have property "%s".', $class->getName(), $name));
        }

        if ($property && !$property->isPublic()) {
            $property->setAccessible(true);
        }

        return $property;
    }

    private static function getReflectionProperty(\ReflectionClass $class, string $name): ?\ReflectionProperty
    {
        try {
            return $class->getProperty($name);
        } catch (\ReflectionException $e) {
            if ($class = $class->getParentClass()) {
                return self::getReflectionProperty($class, $name);
            }
        }

        return null;
    }

    private function instantiate(string $class, array &$attributes): object
    {
        $class = new \ReflectionClass($class);
        $constructor = $class->getConstructor();

        if (self::MODE_ONLY_PROPERTIES === $this->mode || !$constructor || !$constructor->isPublic()) {
            return $class->newInstanceWithoutConstructor();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = self::attributeNameForParameter($parameter, $attributes);

            if (\array_key_exists($name, $attributes)) {
                $arguments[] = $attributes[$name];
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
            return $name;
        }

        // try kebab case
        $name = \str_replace('_', '-', $name);

        if (\array_key_exists($name, $attributes)) {
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
        return \str_replace(' ', '', \preg_replace_callback('/\b./u', static function ($m) use (&$i) {
            return 1 === ++$i ? ('İ' === $m[0] ? 'i̇' : \mb_strtolower($m[0], 'UTF-8')) : \mb_convert_case($m[0], MB_CASE_TITLE, 'UTF-8');
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
        $string = \preg_replace_callback('/\b./u', static function (array $m): string {
            return \mb_convert_case($m[0], MB_CASE_TITLE, 'UTF-8');
        }, $string, 1);

        return \mb_strtolower(\preg_replace(['/(\p{Lu}+)(\p{Lu}\p{Ll})/u', '/([\p{Ll}0-9])(\p{Lu})/u'], '\1_\2', $string), 'UTF-8');
    }
}
