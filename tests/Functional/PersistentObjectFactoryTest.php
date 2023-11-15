<?php

declare(strict_types=1);

namespace Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryNoProxy;

final class PersistentObjectFactoryTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    protected function setUp(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
    }

    /**
     * @test
     */
    public function can_create_one(): void
    {
        $post = PostFactoryNoProxy::createOne();

        self::assertInstanceOf(Post::class, $post);

        PostFactoryNoProxy::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_create_one_from_instance_method(): void
    {
        $post = PostFactoryNoProxy::new()->create(['title' => 'foo']);

        self::assertInstanceOf(Post::class, $post);

        PostFactoryNoProxy::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_create_many(): void
    {
        $posts = PostFactoryNoProxy::createMany(2);

        self::assertCount(2, $posts);
        self::assertContainsOnlyInstancesOf(Post::class, $posts);

        PostFactoryNoProxy::assert()->count(2);
    }

    /**
     * @test
     */
    public function can_create_many_from_instance_method(): void
    {
        $posts = PostFactoryNoProxy::new()->many(2)->create(['title' => 'foo']);

        self::assertCount(2, $posts);
        self::assertContainsOnlyInstancesOf(Post::class, $posts);

        PostFactoryNoProxy::assert()->count(2);
    }

    /**
     * @test
     */
    public function can_create_sequence(): void
    {
        $posts = PostFactoryNoProxy::createSequence([[], []]);

        self::assertCount(2, $posts);
        self::assertContainsOnlyInstancesOf(Post::class, $posts);

        PostFactoryNoProxy::assert()->count(2);
    }

    /**
     * @test
     */
    public function can_find_or_create(): void
    {
        $postCreated = PostFactoryNoProxy::findOrCreate([]);
        $postFetchedFromDb = PostFactoryNoProxy::findOrCreate([]);

        self::assertInstanceOf(Post::class, $postCreated);
        self::assertSame($postCreated, $postFetchedFromDb);

        PostFactoryNoProxy::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_get_first_item(): void
    {
        PostFactoryNoProxy::createSequence([['title' => 'foo'], ['title' => 'bar']]);
        $post = PostFactoryNoProxy::first();

        self::assertInstanceOf(Post::class, $post);
        self::assertSame('foo', $post->getTitle());
    }

    /**
     * @test
     */
    public function can_get_last_item(): void
    {
        PostFactoryNoProxy::createSequence([['title' => 'foo'], ['title' => 'bar']]);
        $post = PostFactoryNoProxy::last();

        self::assertInstanceOf(Post::class, $post);
        self::assertSame('bar', $post->getTitle());
    }

    /**
     * @test
     */
    public function can_get_random_item(): void
    {
        PostFactoryNoProxy::createSequence([['title' => 'foo'], ['title' => 'bar']]);
        $post = PostFactoryNoProxy::random();

        self::assertInstanceOf(Post::class, $post);
    }

    /**
     * @test
     */
    public function can_get_or_create_random_item(): void
    {
        $postCreated = PostFactoryNoProxy::randomOrCreate([]);
        $postFetchedFromDb = PostFactoryNoProxy::randomOrCreate([]);

        self::assertInstanceOf(Post::class, $postCreated);
        self::assertSame($postCreated, $postFetchedFromDb);

        PostFactoryNoProxy::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_get_random_set(): void
    {
        PostFactoryNoProxy::createSequence([['title' => 'foo'], ['title' => 'bar'], ['title' => 'baz']]);
        $posts = PostFactoryNoProxy::randomSet(2);

        self::assertCount(2, $posts);
        self::assertContainsOnlyInstancesOf(Post::class, $posts);
    }

    /**
     * @test
     */
    public function can_get_random_range(): void
    {
        PostFactoryNoProxy::createSequence([['title' => 'foo'], ['title' => 'bar'], ['title' => 'baz']]);
        $posts = PostFactoryNoProxy::randomRange(1, 2);

        self::assertGreaterThanOrEqual(1, \count($posts));
        self::assertLessThanOrEqual(2, \count($posts));
        self::assertContainsOnlyInstancesOf(Post::class, $posts);
    }

    /**
     * @test
     */
    public function can_get_all(): void
    {
        PostFactoryNoProxy::createSequence([['title' => 'foo'], ['title' => 'bar']]);
        $posts = PostFactoryNoProxy::all();

        self::assertCount(2, $posts);
        self::assertContainsOnlyInstancesOf(Post::class, $posts);
    }

    /**
     * @test
     */
    public function can_find_item(): void
    {
        PostFactoryNoProxy::createOne(['title' => 'foo']);
        $post = PostFactoryNoProxy::find(['title' => 'foo']);

        self::assertInstanceOf(Post::class, $post);
        self::assertSame('foo', $post->getTitle());
    }

    /**
     * @test
     */
    public function can_find_by_item(): void
    {
        PostFactoryNoProxy::createMany(2, ['title' => 'foo']);
        $posts = PostFactoryNoProxy::findBy(['title' => 'foo']);

        self::assertCount(2, $posts);
        self::assertContainsOnlyInstancesOf(Post::class, $posts);
    }
}
