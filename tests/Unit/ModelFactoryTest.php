<?php

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ModelFactoryTest extends TestCase
{
    use Factories;

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
}
