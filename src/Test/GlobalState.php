<?php

namespace Zenstruck\Foundry\Test;

use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GlobalState
{
    private static array $callbacks = [];

    public static function add(callable $callback): void
    {
        self::$callbacks[] = $callback;
    }

    /**
     * @internal
     */
    public static function flush(): void
    {
        StoryManager::globalReset();

        foreach (self::$callbacks as $callback) {
            $callback();
        }

        StoryManager::setGlobalState();
    }
}
