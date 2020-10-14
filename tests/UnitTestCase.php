<?php

namespace Zenstruck\Foundry\Tests;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ModelFactoryManager;
use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class UnitTestCase extends TestCase
{
    /** @var Configuration|null */
    protected $configuration;

    /**
     * @before
     */
    public function setUpFoundry(): void
    {
        $this->configuration = new Configuration($this->createMock(ManagerRegistry::class), new StoryManager([]), new ModelFactoryManager([]));

        Factory::boot($this->configuration);
    }
}
