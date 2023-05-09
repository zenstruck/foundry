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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Tag;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\UserFactory;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeOtherObject;

use function Zenstruck\Foundry\anonymous;
use function Zenstruck\Foundry\create;
use function Zenstruck\Foundry\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    protected function setUp(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
    }

    /**
     * @test
     */
    public function many_to_one_relationship(): void
    {
        $categoryFactory = anonymous(Category::class, ['name' => 'foo']);
        $category = create(Category::class, ['name' => 'bar']);
        $postA = create(Post::class, ['title' => 'title', 'body' => 'body', 'category' => $categoryFactory]);
        $postB = create(Post::class, ['title' => 'title', 'body' => 'body', 'category' => $category]);

        $this->assertSame('foo', $postA->getCategory()->getName());
        $this->assertSame('bar', $postB->getCategory()->getName());
    }

    /**
     * @test
     */
    public function one_to_many_relationship(): void
    {
        $category = create(Category::class, [
            'name' => 'bar',
            'posts' => [
                anonymous(Post::class, ['title' => 'Post A', 'body' => 'body']),
                create(Post::class, ['title' => 'Post B', 'body' => 'body']),
            ],
        ]);

        $posts = \array_map(
            static fn($post) => $post->getTitle(),
            $category->getPosts()->toArray()
        );

        $this->assertCount(2, $posts);
        $this->assertContains('Post A', $posts);
        $this->assertContains('Post B', $posts);
    }

    /**
     * @test
     */
    public function many_to_many_relationship(): void
    {
        $post = create(Post::class, [
            'title' => 'title',
            'body' => 'body',
            'tags' => [
                anonymous(Tag::class, ['name' => 'Tag A']),
                create(Tag::class, ['name' => 'Tag B']),
            ],
        ]);

        $tags = \array_map(
            static fn($tag) => $tag->getName(),
            $post->getTags()->toArray()
        );

        $this->assertCount(2, $tags);
        $this->assertContains('Tag A', $tags);
        $this->assertContains('Tag B', $tags);
    }

    /**
     * @test
     */
    public function many_to_many_reverse_relationship(): void
    {
        $tag = create(Tag::class, [
            'name' => 'bar',
            'posts' => [
                anonymous(Post::class, ['title' => 'Post A', 'body' => 'body']),
                create(Post::class, ['title' => 'Post B', 'body' => 'body']),
            ],
        ]);

        $posts = \array_map(
            static fn($post) => $post->getTitle(),
            $tag->getPosts()->toArray()
        );

        $this->assertCount(2, $posts);
        $this->assertContains('Post A', $posts);
        $this->assertContains('Post B', $posts);
    }

    /**
     * @test
     */
    public function creating_with_factory_attribute_persists_the_factory(): void
    {
        $object = anonymous(Post::class)->create([
            'title' => 'title',
            'body' => 'body',
            'category' => anonymous(Category::class, ['name' => 'name']),
        ]);

        $this->assertNotNull($object->getCategory()->getId());
    }

    /**
     * @test
     */
    public function can_create_embeddable(): void
    {
        $object = anonymous(Address::class)->create(['value' => 'an address']);

        $this->assertSame('an address', $object->getValue());
    }

    public function can_delay_flush(): void
    {
        repository(Post::class)->assert()->empty();
        repository(Category::class)->assert()->empty();

        $post = null;
        $return = PersistentObjectFactory::delayFlush(static function() use (&$post): Proxy {
            $post = anonymous(Post::class)->create([
                'title' => 'title',
                'body' => 'body',
                'category' => anonymous(Category::class, ['name' => 'name']),
            ]);
            repository(Post::class)->assert()->empty();
            repository(Category::class)->assert()->empty();

            return $post;
        });

        $this->assertSame($post, $return);

        repository(Post::class)->assert()->count(1);
        repository(Category::class)->assert()->count(1);
    }

    /**
     * @test
     */
    public function auto_refresh_is_disabled_during_delay_flush(): void
    {
        repository(Post::class)->assert()->empty();
        repository(Category::class)->assert()->empty();

        $post = null;
        $return = PersistentObjectFactory::delayFlush(static function() use (&$post): Proxy {
            $post = anonymous(Post::class)->create([
                'title' => 'title',
                'body' => 'body',
                'category' => anonymous(Category::class, ['name' => 'name']),
            ]);
            $post->setTitle('new title');
            $post->setBody('new body');
            repository(Post::class)->assert()->empty();
            repository(Category::class)->assert()->empty();

            return $post;
        });

        $this->assertSame($post, $return);

        repository(Post::class)->assert()->count(1);
        repository(Category::class)->assert()->count(1);
    }

    /**
     * @test
     */
    public function can_create_an_object_not_persisted_with_nested_factory(): void
    {
        $notPersistedObject = SomeObjectFactory::new()->create();
        self::assertInstanceOf(SomeObject::class, $notPersistedObject);
        self::assertInstanceOf(SomeOtherObject::class, $notPersistedObject->someOtherObjectMandatory);
    }

    /**
     * @test
     * @legacy
     */
    public function can_use_legacy_factory(): void
    {
        $post = PostFactory::createOne([
            'comments' => CommentFactory::new()->many(4),
        ]);

        $this->assertCount(4, $post->getComments());
        PostFactory::assert()->count(1);
        CommentFactory::assert()->count(4);
        UserFactory::assert()->count(4);
    }
}
