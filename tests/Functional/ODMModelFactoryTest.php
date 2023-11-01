<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMCategory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMComment;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMPost;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\UserFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMModelFactoryTest extends ModelFactoryTest
{
    protected function setUp(): void
    {
        if (!\getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }
    }

    /**
     * @test
     */
    public function can_use_factory_for_embedded_object(): void
    {
        $proxyObject = CommentFactory::createOne(['user' => new ODMUser('some user'), 'body' => 'some body']);
        self::assertInstanceOf(Proxy::class, $proxyObject);

        $comment = $proxyObject->_real();
        self::assertInstanceOf(ODMComment::class, $comment);
        self::assertEquals(new ODMUser('some user'), $comment->getUser());
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

        $post = $posts[0]->_real();
        self::assertInstanceOf(ODMPost::class, $post);
        self::assertCount(4, $post->getComments());
        self::assertContainsOnlyInstancesOf(ODMComment::class, $post->getComments());
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

    /**
     * @test
     */
    public function can_find_or_create_from_embedded_object(): void
    {
        $post = PostFactory::findOrCreate(['title' => 'foo', 'user' => new ODMUser('some user')]);
        self::assertSame('some user', $post->getUser()->getName());
        PostFactory::assert()->count(1);

        $post2 = PostFactory::findOrCreate(['title' => 'foo', 'user' => new ODMUser('some user')]);
        PostFactory::assert()->count(1);

        self::assertSame($post->_real(), $post2->_real());
    }

    /**
     * @test
     */
    public function can_find_or_create_from_object(): void
    {
        // must disable auto refresh proxy: otherwise doctrine would update post from db and recreate the user object.
        Factory::configuration()->disableDefaultProxyAutoRefresh();

        $user = UserFactory::createOne(['name' => 'some user']);
        $post = PostFactory::findOrCreate($attributes = ['user' => $user->_real()]);

        self::assertSame($user->_real(), $post->getUser());
        PostFactory::assert()->count(1);

        $post2 = PostFactory::findOrCreate($attributes);
        PostFactory::assert()->count(1);

        self::assertSame($post->_real(), $post2->_real());
    }

    /**
     * @test
     */
    public function can_find_or_create_from_proxy_of_object(): void
    {
        // must disable auto refresh proxy: otherwise doctrine would update post from db and recreate the user object.
        Factory::configuration()->disableDefaultProxyAutoRefresh();

        $user = UserFactory::createOne(['name' => 'some user']);
        $post = PostFactory::findOrCreate($attributes = ['user' => $user]);

        self::assertSame($user->_real(), $post->getUser());
        PostFactory::assert()->count(1);

        $post2 = PostFactory::findOrCreate($attributes);
        PostFactory::assert()->count(1);

        self::assertSame($post->_real(), $post2->_real());
    }

    /**
     * @test
     */
    public function can_use_adder_as_attributes(): void
    {
        $post = PostFactory::createOne(['addComment' => CommentFactory::new(['body' => 'body'])]);
        self::assertCount(1, $post->getComments());
        self::assertSame('body', $post->getComments()[0]->getBody());
    }

    protected function categoryClass(): string
    {
        return ODMCategory::class;
    }

    protected function categoryFactoryClass(): string
    {
        return CategoryFactory::class;
    }
}
