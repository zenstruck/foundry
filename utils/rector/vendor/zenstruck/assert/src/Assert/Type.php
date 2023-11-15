<?php

/*
 * This file is part of the zenstruck/assert package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Type
{
    private const BOOL = 'bool';
    private const STRING = 'string';
    private const INT = 'int';
    private const FLOAT = 'float';
    private const NUMERIC = 'numeric';
    private const CALLABLE = 'callable';
    private const RESOURCE = 'resource';
    private const ITERABLE = 'iterable';
    private const COUNTABLE = 'countable';
    private const OBJECT = 'object';
    private const ARRAY = 'array';
    private const ARRAY_LIST = 'array:list';
    private const ARRAY_ASSOC = 'array:assoc';
    private const ARRAY_EMPTY = 'array:empty';
    private const STRING_JSON = 'string:json';

    /** @var string */
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @internal
     *
     * @param mixed $value
     */
    public function __invoke($value): bool
    {
        switch ($this->value) {
            case self::BOOL:
                return \is_bool($value);

            case self::STRING:
                return \is_string($value);

            case self::INT:
                return \is_int($value);

            case self::FLOAT:
                return \is_float($value);

            case self::NUMERIC:
                return \is_numeric($value);

            case self::CALLABLE:
                return \is_callable($value);

            case self::RESOURCE:
                return \is_resource($value);

            case self::OBJECT:
                return \is_object($value);

            case self::ITERABLE:
                return \is_iterable($value);

            case self::COUNTABLE:
                return \is_countable($value);

            case self::ARRAY:
                return \is_array($value);

            case self::ARRAY_LIST:
                return \is_array($value) && $value && array_is_list($value);

            case self::ARRAY_ASSOC:
                return \is_array($value) && $value && !array_is_list($value);

            case self::ARRAY_EMPTY:
                return \is_array($value) && !$value;

            case self::STRING_JSON:
                return self::isJson($value);
        }

        return \is_object($value) && $this->value === $value::class;
    }

    /**
     * @internal
     */
    public function __toString(): string
    {
        return $this->value;
    }

    public static function bool(): self
    {
        return new self(self::BOOL);
    }

    public static function string(): self
    {
        return new self(self::STRING);
    }

    public static function int(): self
    {
        return new self(self::INT);
    }

    public static function float(): self
    {
        return new self(self::FLOAT);
    }

    public static function numeric(): self
    {
        return new self(self::NUMERIC);
    }

    public static function callable(): self
    {
        return new self(self::CALLABLE);
    }

    public static function resource(): self
    {
        return new self(self::RESOURCE);
    }

    public static function iterable(): self
    {
        return new self(self::ITERABLE);
    }

    public static function countable(): self
    {
        return new self(self::COUNTABLE);
    }

    public static function array(): self
    {
        return new self(self::ARRAY);
    }

    public static function arrayList(): self
    {
        return new self(self::ARRAY_LIST);
    }

    public static function arrayAssoc(): self
    {
        return new self(self::ARRAY_ASSOC);
    }

    public static function arrayEmpty(): self
    {
        return new self(self::ARRAY_EMPTY);
    }

    public static function json(): self
    {
        return new self(self::STRING_JSON);
    }

    public static function object(): self
    {
        return new self(self::OBJECT);
    }

    /**
     * @param mixed $value
     */
    private static function isJson($value): bool
    {
        if (!\is_string($value)) {
            return false;
        }

        // TODO: use \JSON_THROW_ON_ERROR once min PHP >= 7.3
        \json_decode($value);

        return \JSON_ERROR_NONE === \json_last_error();
    }
}
