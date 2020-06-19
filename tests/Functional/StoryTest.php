<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\CategoryStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\PostStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\ServiceStory;
use Zenstruck\Foundry\Tests\FunctionalTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StoryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function stories_are_only_loaded_once(): void
    {
        PostFactory::repository()->assertEmpty();

        PostStory::load();
        PostStory::load();
        PostStory::load();

        PostFactory::repository()->assertCount(4);
    }

    /**
     * @test
     */
    public function can_access_managed_proxies_via_magic_call(): void
    {
        $this->assertSame('php', CategoryStory::load()->php()->getName());
    }

    /**
     * @test
     */
    public function can_access_managed_proxies_via_magic_call_static(): void
    {
        $this->assertSame('php', CategoryStory::php()->getName());
    }

    /**
     * @test
     */
    public function cannot_access_invalid_object(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CategoryStory::load()->get('invalid');
    }

    /**
     * @test
     */
    public function stories_can_be_services(): void
    {
        if (!\getenv('USE_FOUNDRY_BUNDLE')) {
            $this->markTestSkipped('Stories cannot be services without the foundry bundle.');
        }

        $this->assertSame('From Service', ServiceStory::post()->getTitle());
    }
}
