<?php

namespace Zenstruck\Foundry\Tests;

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\PersistenceManager;
use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ResetGlobals
{
    /**
     * @before
     */
    public static function resetGlobals()
    {
        $reset = static function($class, $property, $value) {
            $property = (new \ReflectionClass($class))->getProperty($property);
            $property->setAccessible(true);
            $property->setValue($value);
        };

        $reset(Factory::class, 'defaultInstantiator', null);
        $reset(Factory::class, 'faker', null);
        $reset(PersistenceManager::class, 'managerRegistry', null);
        $reset(StoryManager::class, 'globalInstances', []);
        $reset(StoryManager::class, 'instances', []);
    }
}
