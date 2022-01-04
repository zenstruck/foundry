<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Document\Category;
use Zenstruck\Foundry\Tests\Fixtures\Document\Comment;
use Zenstruck\Foundry\Tests\Fixtures\Document\Post;
use Zenstruck\Foundry\Tests\Fixtures\Document\User;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMModelFactoryTest extends ModelFactoryTest
{
    protected function setUp(): void
    {
        if (false === \getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }
    }

    /**
     * @test
     */
    public function can_use_factory_for_embedded_object(): void
    {
        $proxyObject = CommentFactory::createOne(['user' => new User('some user'), 'body' => 'some body']);
        self::assertInstanceOf(Proxy::class, $proxyObject);
        self::assertFalse($proxyObject->isPersisted());

        $comment = $proxyObject->object();
        self::assertInstanceOf(Comment::class, $comment);
        self::assertEquals(new User('some user'), $comment->getUser());
        self::assertSame('some body', $comment->getBody());
    }

    /**
     * @test
     */
    public function can_hydrate_embed_many_fields(): void
    {
        PostFactory::createOne([
            'title' => 'foo',
            'comments' => CommentFactory::new()->many(4),
        ]);

        $posts = PostFactory::findBy(['title' => 'foo']);
        self::assertCount(1, $posts);

        $post = $posts[0]->object();
        self::assertInstanceOf(Post::class, $post);
        self::assertCount(4, $post->getComments());
        self::assertContainsOnlyInstancesOf(Comment::class, $post->getComments());
    }

    /**
     * @test
     */
    public function can_create_one_with_nested_embedded(): void
    {
        PostFactory::new()->withComments()->create(['title' => 'foo']);

        $posts = PostFactory::findBy(['title' => 'foo']);
        self::assertCount(1, $posts);
    }

    protected function categoryClass(): string
    {
        return Category::class;
    }

    protected function categoryFactoryClass(): string
    {
        return CategoryFactory::class;
    }
}
