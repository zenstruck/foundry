<?php

namespace Zenstruck\Foundry;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StoryManager
{
    /** @var array<string, Story> */
    private static array $globalInstances = [];

    /** @var array<string, Story> */
    private static array $instances = [];

    public static function has(string $story): bool
    {
        return \array_key_exists($story, self::$globalInstances) || \array_key_exists($story, self::$instances);
    }

    public static function get(string $story): Story
    {
        if (\array_key_exists($story, self::$globalInstances)) {
            return self::$globalInstances[$story];
        }

        return self::$instances[$story];
    }

    public static function set(Story $story): void
    {
        self::$instances[\get_class($story)] = $story;
    }

    public static function setGlobalState(): void
    {
        self::$globalInstances = self::$instances;
        self::$instances = [];
    }

    public static function reset(): void
    {
        self::$instances = [];
    }

    public static function globalReset(): void
    {
        self::$globalInstances = self::$instances = [];
    }
}
