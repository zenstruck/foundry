<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityPoolStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\PersistenceDisabledStory;
use Zenstruck\Foundry\Tests\Integration\Persistence\StoryTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class OrmStoryTest extends StoryTestCase
{
    use RequiresORM;

    /**
     * @test
     */
    #[Test]
    public function can_use_story_with_persistence_disabled(): void
    {
        PersistenceDisabledStory::load();
        self::assertInstanceOf(GenericEntity::class, PersistenceDisabledStory::foo());
    }

    protected static function storyClass(): string
    {
        return EntityStory::class;
    }

    protected static function factoryClass(): string
    {
        return GenericEntityFactory::class;
    }

    protected static function poolStoryClass(): string
    {
        return EntityPoolStory::class;
    }
}
