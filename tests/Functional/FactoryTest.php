<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\AnonymousFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Tag;
use function Zenstruck\Foundry\create;
use function Zenstruck\Foundry\factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    protected function setUp(): void
    {
        if (false === \getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
    }

    /**
     * @test
     */
    public function many_to_one_relationship(): void
    {
        $categoryFactory = factory(Category::class, ['name' => 'foo']);
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
                factory(Post::class, ['title' => 'Post A', 'body' => 'body']),
                create(Post::class, ['title' => 'Post B', 'body' => 'body']),
            ],
        ]);

        $posts = \array_map(
            static function($post) {
                return $post->getTitle();
            },
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
                factory(Tag::class, ['name' => 'Tag A']),
                create(Tag::class, ['name' => 'Tag B']),
            ],
        ]);

        $tags = \array_map(
            static function($tag) {
                return $tag->getName();
            },
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
                factory(Post::class, ['title' => 'Post A', 'body' => 'body']),
                create(Post::class, ['title' => 'Post B', 'body' => 'body']),
            ],
        ]);

        $posts = \array_map(
            static function($post) {
                return $post->getTitle();
            },
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
        $object = (new AnonymousFactory(Post::class))->create([
            'title' => 'title',
            'body' => 'body',
            'category' => new AnonymousFactory(Category::class, ['name' => 'name']),
        ]);

        $this->assertNotNull($object->getCategory()->getId());
    }

    /**
     * @test
     */
    public function can_create_embeddable(): void
    {
        $object1 = (new AnonymousFactory(Address::class))->create();
        $object2 = (new AnonymousFactory(Address::class))->create(['value' => 'an address']);

        $this->assertNull($object1->getValue());
        $this->assertSame('an address', $object2->getValue());
    }

    public function can_delay_flush(): void
    {
        AnonymousFactory::new(Post::class)->assert()->empty();
        AnonymousFactory::new(Category::class)->assert()->empty();

        AnonymousFactory::delayFlush(function() {
            AnonymousFactory::new(Post::class)->create([
                'title' => 'title',
                'body' => 'body',
                'category' => AnonymousFactory::new(Category::class, ['name' => 'name']),
            ]);

            AnonymousFactory::new(Post::class)->assert()->empty();
            AnonymousFactory::new(Category::class)->assert()->empty();
        });

        AnonymousFactory::new(Post::class)->assert()->count(1);
        AnonymousFactory::new(Category::class)->assert()->count(1);
    }

    /**
     * @test
     */
    public function auto_refresh_is_disabled_during_delay_flush(): void
    {
        AnonymousFactory::new(Post::class)->assert()->empty();
        AnonymousFactory::new(Category::class)->assert()->empty();

        AnonymousFactory::delayFlush(function() {
            $post = AnonymousFactory::new(Post::class)->create([
                'title' => 'title',
                'body' => 'body',
                'category' => AnonymousFactory::new(Category::class, ['name' => 'name']),
            ]);

            $post->setTitle('new title');
            $post->setBody('new body');

            AnonymousFactory::new(Post::class)->assert()->empty();
            AnonymousFactory::new(Category::class)->assert()->empty();
        });

        AnonymousFactory::new(Post::class)->assert()->count(1);
        AnonymousFactory::new(Category::class)->assert()->count(1);
    }
}
