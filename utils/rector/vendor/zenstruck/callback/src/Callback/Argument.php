<?php

namespace Zenstruck\Callback;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Argument
{
    /**
     * Allow exact type (always enabled).
     */
    public const EXACT = 0;

    /**
     * If type is class, parent classes are supported.
     */
    public const COVARIANCE = 2;

    /**
     * If type is class, child classes are supported.
     */
    public const CONTRAVARIANCE = 4;

    /**
     * If type is string, do not support other scalar types. Follows
     * same logic as "declare(strict_types=1)".
     */
    public const STRICT = 8;

    /**
     * If type is float, do not support int (implies {@see STRICT).
     */
    public const VERY_STRICT = 16;

    private const TYPE_NORMALIZE_MAP = [
        'boolean' => 'bool',
        'integer' => 'int',
        'double' => 'float',
        'resource (closed)' => 'resource',
    ];

    private const ALLOWED_TYPE_MAP = [
        'string' => ['bool', 'int', 'float'],
        'bool' => ['string', 'int', 'float'],
        'float' => ['string', 'int', 'bool'],
        'int' => ['string', 'float', 'bool'],
    ];

    /** @var \ReflectionParameter */
    private $parameter;

    /** @var ?\ReflectionType */
    private $reflectionType;

    /** @var string[] */
    private $types;

    /** @var ?string */
    private $type;

    public function __construct(\ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;
        $this->reflectionType = $parameter->getType();
    }

    public function __toString(): string
    {
        return (string) $this->type();
    }

    public function type(): ?string
    {
        if (isset($this->type)) {
            return $this->type;
        }

        return $this->type = $this->hasType() ? \implode($this->isIntersectionType() ? '&' : '|', $this->types()) : null;
    }

    /**
     * @return string[]
     */
    public function types(): array
    {
        if (isset($this->types)) {
            return $this->types;
        }

        return $this->types = \array_map(
            function(\ReflectionNamedType $type) {
                if ('self' !== $name = $type->getName()) {
                    return $name;
                }

                if (!$class = $this->parameter->getDeclaringClass()) {
                    throw new \LogicException('Unable to parse context of "self" typehint.');
                }

                return $class->name;
            },
            $this->reflectionTypes()
        );
    }

    public function hasType(): bool
    {
        return (bool) $this->reflectionType;
    }

    public function isNamedType(): bool
    {
        return $this->reflectionType instanceof \ReflectionNamedType;
    }

    public function isUnionType(): bool
    {
        return $this->reflectionType instanceof \ReflectionUnionType;
    }

    public function isIntersectionType(): bool
    {
        return $this->reflectionType instanceof \ReflectionIntersectionType;
    }

    public function isOptional(): bool
    {
        return $this->parameter->isOptional();
    }

    /**
     * @return mixed
     */
    public function defaultValue()
    {
        return $this->parameter->getDefaultValue();
    }

    /**
     * @param string $type    The type to check if this argument supports
     * @param int    $options {@see EXACT}, {@see COVARIANCE}, {@see CONTRAVARIANCE}
     *                        Bitwise disjunction of above is allowed
     */
    public function supports(string $type, int $options = self::EXACT|self::COVARIANCE): bool
    {
        if (!$this->reflectionType) {
            // no type-hint so any type is supported
            return true;
        }

        if ($this->reflectionType instanceof \ReflectionIntersectionType) {
            foreach ($this->reflectionType->getTypes() as $refType) {
                $arg = clone $this;
                $arg->reflectionType = $refType;

                if (!$arg->supports($type)) {
                    return false;
                }
            }

            return true;
        }

        if ('null' === \mb_strtolower($type) && $this->parameter->allowsNull()) {
            return true;
        }

        $type = self::TYPE_NORMALIZE_MAP[$type] ?? $type;

        foreach ($this->types() as $supportedType) {
            if ($supportedType === $type) {
                return true;
            }

            if ($options & self::COVARIANCE && \is_a($type, $supportedType, true)) {
                return true;
            }

            if ($options & self::CONTRAVARIANCE && \is_a($supportedType, $type, true)) {
                return true;
            }

            if ($options & self::VERY_STRICT) {
                continue;
            }

            if ('float' === $supportedType && 'int' === $type) {
                // strict typing allows int to pass a float validation
                return true;
            }

            if ($options & self::STRICT) {
                continue;
            }

            if (\in_array($type, self::ALLOWED_TYPE_MAP[$supportedType] ?? [], true)) {
                return true;
            }

            if ('string' === $supportedType && \method_exists($type, '__toString')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     * @param bool  $strict {@see STRICT}
     */
    public function allows($value, bool $strict = false): bool
    {
        if (!$this->hasType()) {
            // no type-hint so any type is supported
            return true;
        }

        $type = \is_object($value) ? \get_class($value) : \gettype($value);
        $type = self::TYPE_NORMALIZE_MAP[$type] ?? $type;
        $options = $strict ? self::EXACT|self::COVARIANCE|self::STRICT : self::EXACT|self::COVARIANCE;
        $supports = $this->supports($type, $options);

        if (!$supports) {
            return false;
        }

        if ('string' === $type && !\is_numeric($value) && !\in_array('string', $this->types(), true)) {
            // non-numeric strings cannot be used for float/int
            return false;
        }

        return true;
    }

    /**
     * @return \ReflectionNamedType[]
     */
    private function reflectionTypes(): array
    {
        if (!$this->reflectionType) {
            return [];
        }

        if ($this->reflectionType instanceof \ReflectionNamedType) {
            return [$this->reflectionType];
        }

        /** @var \ReflectionUnionType $type */
        return $this->reflectionType->getTypes();
    }
}
