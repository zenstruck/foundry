<?php

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CustomFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function can_set_states_with_method(): void
    {
        $this->assertFalse(PostFactory::new()->instantiate()->isPublished());
        $this->assertTrue(PostFactory::new()->published()->instantiate()->isPublished());
    }

    /**
     * @test
     */
    public function can_set_state_via_new(): void
    {
        $this->assertFalse(PostFactory::new()->instantiate()->isPublished());
        $this->assertTrue(PostFactory::new('published')->instantiate()->isPublished());
    }

    /**
     * @test
     */
    public function can_instantiate(): void
    {
        $this->assertSame('title', PostFactory::new()->instantiate(['title' => 'title'])->getTitle());
    }

    /**
     * @test
     */
    public function can_instantiate_many(): void
    {
        $objects = PostFactory::new()->instantiateMany(2, ['title' => 'title']);

        $this->assertCount(2, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
    }
}
