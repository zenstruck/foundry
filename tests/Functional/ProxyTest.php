<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\FunctionalTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProxyTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function can_assert_persisted(): void
    {
        $post = PostFactory::create();

        $post->assertPersisted();
    }

    /**
     * @test
     */
    public function can_remove_and_assert_not_persisted(): void
    {
        $post = PostFactory::create();

        $post->remove();

        $post->assertNotPersisted();
    }

    /**
     * @test
     */
    public function functions_are_passed_to_wrapped_object(): void
    {
        $post = PostFactory::create(['title' => 'my title']);

        $this->assertSame('my title', $post->getTitle());
    }

    /**
     * @test
     */
    public function can_convert_to_string_if_wrapped_object_can(): void
    {
        $post = PostFactory::create(['title' => 'my title']);

        $this->assertSame('my title', (string) $post);
    }

    /**
     * @test
     */
    public function cannot_convert_to_string_if_underlying_object_cant(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('Proxied object "%s" cannot be converted to a string.', Category::class));

        (string) CategoryFactory::create();
    }

    /**
     * @test
     */
    public function can_refetch_object_if_object_manager_has_been_cleared(): void
    {
        $post = PostFactory::create(['title' => 'my title']);

        self::$container->get('doctrine')->getManager()->clear();

        $this->assertSame('my title', $post->refresh()->getTitle());
    }

    /**
     * @test
     */
    public function exception_thrown_if_trying_to_refresh_deleted_object(): void
    {
        $post = PostFactory::create();

        self::$container->get('doctrine')->getManager()->clear();

        PostFactory::repository()->truncate();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The object no longer exists.');

        $post->refresh();
    }

    /**
     * @test
     */
    public function can_force_set_and_save(): void
    {
        $post = PostFactory::create(['title' => 'my title']);

        $post->repository()->assertNotExists(['title' => 'new title']);

        $post->forceSet('title', 'new title')->save();

        $post->repository()->assertExists(['title' => 'new title']);
    }
}
