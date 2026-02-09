<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;
use Zenstruck\Foundry\Tests\Fixture\Object1;
use Zenstruck\Foundry\Tests\Fixture\Stories\DocumentPoolStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\DocumentStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityPoolStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\ObjectStory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[ResetDatabase]
abstract class StoryTestCase extends KernelTestCase
{
    /**
     * @test
     */
    #[Test]
    public function stories_only_loaded_once(): void
    {
        static::factoryClass()::repository()->assert()->empty();

        static::storyClass()::load();
        static::storyClass()::load();
        static::storyClass()::load();

        static::factoryClass()::repository()->assert()->count(2);
    }

    /**
     * @test
     */
    #[Test]
    public function can_access_story_state(): void
    {
        $story = static::storyClass();

        $this->assertSame('foo', $story::get('foo')->getProp1());
        $this->assertSame('bar', $story::get('bar')->getProp1());
        $this->assertSame(12, $story::get('int'));
        $this->assertSame(12.12, $story::get('float'));
        $this->assertSame('dummyString', $story::get('string'));
        $this->assertTrue($story::get('bool'));
        $this->assertSame([12, 'dummyString', [true, 12.12]], $story::get('array'));
        $this->assertNull($story::get('null'));
    }

    /**
     * @test
     */
    #[Test]
    public function can_access_story_state_with_magic_call(): void
    {
        $story = static::storyClass();

        $this->assertSame('foo', $story::foo()->getProp1());
        $this->assertSame('bar', $story::bar()->getProp1());
        $this->assertSame(12, $story::int());
        $this->assertSame(12.12, $story::float());
        $this->assertSame('dummyString', $story::string());
        $this->assertTrue($story::bool());
        $this->assertSame([12, 'dummyString', [true, 12.12]], $story::array());
        $this->assertNull($story::null());
    }

    /**
     * @test
     */
    #[Test]
    public function can_access_story_state_with_magic_call_on_instance(): void
    {
        $story = static::storyClass();

        $this->assertSame('foo', $story::load()->foo()->getProp1());
        $this->assertSame('bar', $story::load()->bar()->getProp1());
        $this->assertSame(12, $story::load()->int());
        $this->assertSame(12.12, $story::load()->float());
        $this->assertSame('dummyString', $story::load()->string());
        $this->assertTrue($story::load()->bool());
        $this->assertSame([12, 'dummyString', [true, 12.12]], $story::load()->array());
        $this->assertNull($story::load()->null());
    }

    /**
     * @test
     */
    #[Test]
    public function cannot_access_invalid_object(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        static::storyClass()::get('invalid');
    }

    /**
     * @test
     */
    #[Test]
    public function can_get_random_object_set_from_pool(): void
    {
        $story = static::poolStoryClass();

        $objects = $story::getRandomSet($story, 2);

        $this->assertCount(2, $objects);
    }

    /**
     * @test
     */
    #[Test]
    public function can_get_random_object_from_pool(): void
    {
        $story = static::poolStoryClass();

        $ids = [];

        while (50 !== \count($ids) && 3 !== \count(\array_unique($ids))) {
            $ids[] = $story::getRandom($story)->id;
        }

        $this->assertCount(3, \array_unique($ids));
    }

    /**
     * @test
     */
    #[Test]
    public function can_get_random_object_range_from_pool(): void
    {
        $story = static::poolStoryClass();

        $counts = [];

        while (3 !== \count(\array_unique($counts))) {
            $counts[] = \count($story::getRandomRange($story, 0, 2));
        }

        $this->assertCount(3, \array_unique($counts));
        $this->assertContains(0, $counts);
        $this->assertContains(1, $counts);
        $this->assertContains(2, $counts);
        $this->assertNotContains(3, $counts);
        $this->assertNotContains(4, $counts);
    }

    /**
     * @test
     */
    #[Test]
    public function story_can_access_its_own_pool(): void
    {
        $story = static::poolStoryClass();

        $item = $story::get('random-from-own-pool');

        self::assertInstanceOf(GenericModel::class, $item);

        self::assertContains($item->getProp1(), ['foo', 'default1']);
    }

    /**
     * @test
     */
    #[Test]
    public function can_use_story_with_simple_object(): void
    {
        ObjectStory::load();
        self::assertInstanceOf(Object1::class, ObjectStory::foo());
    }

    /** @return class-string<DocumentStory|EntityStory> */
    abstract protected static function storyClass(): string;

    /** @return class-string<PersistentObjectFactory<GenericModel>> */
    abstract protected static function factoryClass(): string;

    /** @return class-string<DocumentPoolStory|EntityPoolStory> */
    abstract protected static function poolStoryClass(): string;
}
