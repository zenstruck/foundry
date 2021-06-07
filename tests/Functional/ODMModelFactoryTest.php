<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Document\Category;
use Zenstruck\Foundry\Tests\Fixtures\Document\Comment;
use Zenstruck\Foundry\Tests\Fixtures\Document\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMModelFactoryTest extends ModelFactoryTest
{
    public function setUp(): void
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
        $proxyObject = CommentFactory::createOne(['user' => 'some user', 'body' => 'some body']);
        self::assertInstanceOf(Proxy::class, $proxyObject);
        self::assertFalse($proxyObject->isPersisted());

        $comment = $proxyObject->object();
        self::assertInstanceOf(Comment::class, $comment);
        self::assertSame('some user', $comment->getUser());
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

    protected function categoryClass(): string
    {
        return Category::class;
    }

    protected function categoryFactoryClass(): string
    {
        return CategoryFactory::class;
    }
}
