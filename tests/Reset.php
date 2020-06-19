<?php

namespace Zenstruck\Foundry\Tests;

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Manager;
use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Reset
{
    /**
     * @before
     */
    public static function reset()
    {
        $reset = static function($class, $property, $value) {
            $property = (new \ReflectionClass($class))->getProperty($property);
            $property->setAccessible(true);
            $property->setValue($value);
        };

        $reset(StoryManager::class, 'globalInstances', []);
        $reset(StoryManager::class, 'instances', []);

        Factory::boot(new Manager());
    }
}
