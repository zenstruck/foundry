<?php

namespace Zenstruck\Foundry\Test;

use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\PersistenceManager;
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
    public static function flush(ManagerRegistry $registry): void
    {
        StoryManager::globalReset();

        PersistenceManager::register($registry);

        foreach (self::$callbacks as $callback) {
            $callback();
        }

        StoryManager::setGlobalState();
    }
}
