<?php

namespace Zenstruck\Foundry\Tests;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Manager;
use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class UnitTestCase extends TestCase
{
    protected ?Manager $manager = null;

    /**
     * @before
     */
    public function setUpFoundry(): void
    {
        $reset = static function($class, $property, $value) {
            $property = (new \ReflectionClass($class))->getProperty($property);
            $property->setAccessible(true);
            $property->setValue($value);
        };

        $reset(StoryManager::class, 'globalInstances', []);
        $reset(StoryManager::class, 'instances', []);

        Factory::boot($this->manager = new Manager($this->createMock(ManagerRegistry::class), new StoryManager([])));
    }
}
