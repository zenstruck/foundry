<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\CategoryPoolStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\CategoryStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\PostStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\ServiceStory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StoryTest extends KernelTestCase
{
    use ExpectDeprecationTrait, Factories, ResetDatabase;

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

    /**
     * @test
     * @group legacy
     */
    public function calling_add_is_deprecated(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.17.0: Using Story::add() is deprecated, use Story::addState().');

        CategoryFactory::assert()->empty();

        CategoryStory::load()->add('foo', CategoryFactory::new());

        CategoryFactory::assert()->count(3);
    }

    /**
     * @test
     */
    public function can_get_random_object_from_pool(): void
    {
        $ids = [];

        while (5 !== \count(\array_unique($ids))) {
            $ids[] = CategoryPoolStory::getRandom('pool-name')->getId();
        }

        $this->assertCount(5, \array_unique($ids));
    }

    /**
     * @test
     */
    public function can_get_random_object_set_from_pool(): void
    {
        $objects = CategoryPoolStory::getRandomSet('pool-name', 3);

        $this->assertCount(3, $objects);
        $this->assertCount(3, \array_unique(\array_map(static function($category) { return $category->getId(); }, $objects)));
    }

    /**
     * @test
     */
    public function can_get_random_object_range_from_pool(): void
    {
        $counts = [];

        while (4 !== \count(\array_unique($counts))) {
            $counts[] = \count(CategoryPoolStory::getRandomRange('pool-name', 0, 3));
        }

        $this->assertCount(4, \array_unique($counts));
        $this->assertContains(0, $counts);
        $this->assertContains(1, $counts);
        $this->assertContains(2, $counts);
        $this->assertContains(3, $counts);
        $this->assertNotContains(4, $counts);
        $this->assertNotContains(5, $counts);
    }

    /**
     * @test
     */
    public function random_set_number_must_be_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CategoryPoolStory::getRandomSet('pool-name', 0);
    }

    /**
     * @test
     */
    public function random_range_min_must_be_zero_or_greater(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CategoryPoolStory::getRandomRange('pool-name', -1, 25);
    }

    /**
     * @test
     */
    public function random_range_min_must_be_less_than_max(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CategoryPoolStory::getRandomRange('pool-name', 50, 25);
    }

    /**
     * @test
     */
    public function random_range_more_than_available(): void
    {
        $this->expectException(\RuntimeException::class);

        CategoryPoolStory::getRandomRange('pool-name', 0, 100);
    }
}
