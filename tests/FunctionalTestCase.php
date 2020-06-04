<?php

namespace Zenstruck\Foundry\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\StoryManager;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class FunctionalTestCase extends KernelTestCase
{
    use ResetDatabase, Factories;

    protected function setUp(): void
    {
        parent::setUp();

        CategoryFactory::repository()->truncate();
        StoryManager::globalReset();
    }
}
