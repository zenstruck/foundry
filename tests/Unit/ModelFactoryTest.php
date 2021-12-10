<?php

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\TagFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ModelFactoryTest extends TestCase
{
    use ExpectDeprecationTrait, Factories;

    /**
     * @test
     */
    public function can_set_states_with_method(): void
    {
        $this->assertFalse(PostFactory::createOne()->isPublished());
        $this->assertTrue(PostFactory::new()->published()->create()->isPublished());
    }

    /**
     * @test
     */
    public function can_set_state_via_new(): void
    {
        $this->assertFalse(PostFactory::createOne()->isPublished());
        $this->assertTrue(PostFactory::new('published')->create()->isPublished());
    }

    /**
     * @test
     */
    public function can_instantiate(): void
    {
        $this->assertSame('title', PostFactory::new()->create(['title' => 'title'])->getTitle());
        $this->assertSame('title', PostFactory::createOne(['title' => 'title'])->getTitle());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_instantiate_many_legacy(): void
    {
        $this->expectDeprecation(\sprintf('Since zenstruck/foundry 1.7: Calling instance method "%1$s::createMany()" is deprecated and will be removed in 2.0, use the static "%1$s:createMany()" method instead.', PostFactory::class));

        $objects = PostFactory::new(['body' => 'body'])->createMany(2, ['title' => 'title']);

        $this->assertCount(2, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('body', $objects[1]->getBody());
    }

    /**
     * @test
     */
    public function can_instantiate_many(): void
    {
        $objects = PostFactory::createMany(2, ['title' => 'title']);

        $this->assertCount(2, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
    }

    /**
     * @test
     */
    public function create_with_many_to_one_relation(): void
    {
        $object = PostFactory::createOne(['category' => CategoryFactory::new(['name' => 'My Name'])]);

        $this->assertNull($object->getId());
        $this->assertNull($object->getCategory()->getId());
        $this->assertSame('My Name', $object->getCategory()->getName());
    }

    /**
     * @test
     */
    public function create_with_one_to_many_relation(): void
    {
        $post = PostFactory::new()->create([
            'comments' => CommentFactory::new()->many(4),
        ]);

        $this->assertNull($post->getId());
        $this->assertCount(4, $post->getComments());
    }

    /**
     * @test
     */
    public function create_with_many_to_many_relation(): void
    {
        $post = PostFactory::createOne([
            'tags' => TagFactory::new()->many(3),
        ]);

        $this->assertNull($post->getId());
        $this->assertCount(3, $post->getTags());
    }
}
