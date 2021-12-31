<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\CategoryStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\PostStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\ServiceStory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StoryTest extends KernelTestCase
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
    public function stories_are_only_loaded_once(): void
    {
        PostFactory::assert()->empty();

        PostStory::load();
        PostStory::load();
        PostStory::load();

        PostFactory::assert()->count(4);
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

    /**
     * @test
     */
    public function service_stories_cannot_be_used_without_the_bundle(): void
    {
        if (\getenv('USE_FOUNDRY_BUNDLE')) {
            $this->markTestSkipped('ZenstruckFoundryBundle enabled.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stories with dependencies (Story services) cannot be used without the foundry bundle.');

        ServiceStory::load();
    }
}
