<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Factories\TagFactory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\TagStory;

final class ORMGlobalStateTest extends GlobalStateTest
{
    protected function setUp(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        parent::setUp();
    }

    protected function getTagFactoryClass(): string
    {
        return TagFactory::class;
    }

    protected function getTagStoryClass(): string
    {
        return TagStory::class;
    }
}
