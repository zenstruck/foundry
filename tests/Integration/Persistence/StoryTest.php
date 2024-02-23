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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Document\GlobalDocument;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;
use Zenstruck\Foundry\Tests\Fixture\Stories\DocumentPoolStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\DocumentStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;

use Zenstruck\Foundry\Tests\Fixture\Stories\EntityPoolStory;

use function Zenstruck\Foundry\Persistence\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StoryTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @return iterable<array{class-string<Story>, class-string<PersistentObjectFactory<GenericModel>>}>
     */
    public static function storiesProvider(): iterable
    {
        if (\getenv('DATABASE_URL')) {
            yield [EntityStory::class, GenericEntityFactory::class];
        }

        if (\getenv('MONGO_URL')) {
            yield [DocumentStory::class, GenericDocumentFactory::class];
        }
    }

    /**
     * @param class-string<Story>                                 $story
     * @param class-string<PersistentObjectFactory<GenericModel>> $factory
     *
     * @test
     * @dataProvider storiesProvider
     */
    public function stories_only_loaded_once(string $story, string $factory): void
    {
        $factory::repository()->assert()->empty();

        $story::load();
        $story::load();
        $story::load();

        $factory::repository()->assert()->count(2);
    }

    /**
     * @test
     */
    public function global_stories_are_loaded(): void
    {
        if (!\getenv('DATABASE_URL') && !\getenv('MONGO_URL')) {
            $this->markTestSkipped('No persistence enabled.');
        }

        if (\getenv('DATABASE_URL')) {
            repository(GlobalEntity::class)->assert()->count(2);
        }

        if (\getenv('MONGO_URL')) {
            repository(GlobalDocument::class)->assert()->count(2);
        }
    }

    /**
     * @test
     */
    public function global_stories_cannot_be_loaded_again(): void
    {
        if (!\getenv('DATABASE_URL') && !\getenv('MONGO_URL')) {
            $this->markTestSkipped('No persistence enabled.');
        }

        GlobalStory::load();

        if (\getenv('DATABASE_URL')) {
            repository(GlobalEntity::class)->assert()->count(2);
        }

        if (\getenv('MONGO_URL')) {
            repository(GlobalDocument::class)->assert()->count(2);
        }
    }

    /**
     * @param class-string<EntityStory|DocumentStory> $story
     *
     * @test
     * @dataProvider storiesProvider
     */
    public function can_access_story_state(string $story): void
    {
        $this->assertSame('foo', $story::get('foo')->getProp1());
        $this->assertSame('bar', $story::get('bar')->getProp1());
    }

    /**
     * @param class-string<EntityStory|DocumentStory> $story
     *
     * @test
     * @dataProvider storiesProvider
     */
    public function can_access_story_state_with_magic_call(string $story): void
    {
        $this->assertSame('foo', $story::foo()->getProp1());
        $this->assertSame('bar', $story::bar()->getProp1());
    }

    /**
     * @param class-string<EntityStory|DocumentStory> $story
     *
     * @test
     * @dataProvider storiesProvider
     */
    public function can_access_story_state_with_magic_call_on_instance(string $story): void
    {
        $this->assertSame('foo', $story::load()->foo()->getProp1());
        $this->assertSame('bar', $story::load()->bar()->getProp1());
    }

    /**
     * @param class-string<EntityStory|DocumentStory> $story
     *
     * @test
     * @dataProvider storiesProvider
     */
    public function cannot_access_invalid_object(string $story): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $story::get('invalid');
    }

    /**
     * @return iterable<array{class-string<Story>}>
     */
    public static function poolStoriesProvider(): iterable
    {
        if (\getenv('DATABASE_URL')) {
            yield [EntityPoolStory::class];
        }

        if (\getenv('MONGO_URL')) {
            yield [DocumentPoolStory::class];
        }
    }

    /**
     * @param class-string<Story> $story
     *
     * @test
     * @dataProvider poolStoriesProvider
     */
    public function can_get_random_object_set_from_pool(string $story): void
    {
        $objects = $story::getRandomSet($story, 2);

        $this->assertCount(2, $objects);
    }

    /**
     * @param class-string<Story> $story
     *
     * @test
     * @dataProvider poolStoriesProvider
     */
    public function can_get_random_object_from_pool(string $story): void
    {
        $ids = [];

        while (50 !== \count($ids) && 3 !== \count(\array_unique($ids))) {
            $ids[] = $story::getRandom($story)->id;
        }

        $this->assertCount(3, \array_unique($ids));
    }

    /**
     * @param class-string<Story> $story
     *
     * @test
     * @dataProvider poolStoriesProvider
     */
    public function can_get_random_object_range_from_pool(string $story): void
    {
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
     * @param class-string<Story> $story
     *
     * @test
     * @dataProvider poolStoriesProvider
     */
    public function story_can_access_its_own_pool(string $story): void
    {
        $item = $story::get('random-from-own-pool');

        self::assertInstanceOf(GenericModel::class, $item);

        self::assertContains($item->getProp1(), ['foo', 'default1']);
    }
}
