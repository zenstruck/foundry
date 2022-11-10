<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\TagFactory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\ODMTagStory;

final class ODMGlobalStateTest extends GlobalStateTest
{
    protected function setUp(): void
    {
        if (!\getenv('USE_ODM')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }

        if (\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
            self::markTestSkipped('ODM Global state cannot be handled with dama/doctrine-test-bundle.');
        }

        parent::setUp();
    }

    protected function getTagFactoryClass(): string
    {
        return TagFactory::class;
    }

    protected function getTagStoryClass(): string
    {
        return ODMTagStory::class;
    }
}
