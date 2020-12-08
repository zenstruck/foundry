<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProxyTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     */
    public function can_assert_persisted(): void
    {
        $post = PostFactory::new()->create();

        $post->assertPersisted();
    }

    /**
     * @test
     */
    public function can_remove_and_assert_not_persisted(): void
    {
        $post = PostFactory::new()->create();

        $post->remove();

        $post->assertNotPersisted();
    }

    /**
     * @test
     */
    public function functions_are_passed_to_wrapped_object(): void
    {
        $post = PostFactory::new()->create(['title' => 'my title']);

        $this->assertSame('my title', $post->getTitle());
    }

    /**
     * @test
     */
    public function can_convert_to_string_if_wrapped_object_can(): void
    {
        $post = PostFactory::new()->create(['title' => 'my title']);

        $this->assertSame('my title', (string) $post);
    }

    /**
     * @test
     * @requires PHP >= 7.4
     */
    public function cannot_convert_to_string_if_underlying_object_cant(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('Proxied object "%s" cannot be converted to a string.', Category::class));

        (string) CategoryFactory::new()->create();
    }

    /**
     * @test
     * @requires PHP < 7.4
     */
    public function on_php_versions_less_than_7_4_if_underlying_object_is_missing_to_string_proxy_to_string_returns_note(): void
    {
        $this->assertSame('(no __toString)', (string) CategoryFactory::new()->create());
    }

    /**
     * @test
     */
    public function can_refetch_object_if_object_manager_has_been_cleared(): void
    {
        $post = PostFactory::new()->create(['title' => 'my title']);

        self::$container->get('doctrine')->getManager()->clear();

        $this->assertSame('my title', $post->refresh()->getTitle());
    }

    /**
     * @test
     */
    public function exception_thrown_if_trying_to_refresh_deleted_object(): void
    {
        $post = PostFactory::new()->create();

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
        $post = PostFactory::new()->create(['title' => 'old title']);

        $post->repository()->assertNotExists(['title' => 'new title']);

        $post->forceSet('title', 'new title')->save();

        $post->repository()->assertExists(['title' => 'new title']);
    }

    /**
     * @test
     */
    public function can_force_set_multiple_fields(): void
    {
        $post = PostFactory::new()->create(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->forceSet('title', 'new title')
            ->forceSet('body', 'new body')
            ->save()
        ;

        $this->assertSame('new title', $post->getTitle());
        $this->assertSame('new body', $post->getBody());
    }

    /**
     * @test
     */
    public function demonstrate_setting_field_problem_with_auto_refreshing_enabled(): void
    {
        $post = PostFactory::new()->create(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->enableAutoRefresh()
            ->forceSet('title', 'new title') // will not be saved because the following ->forceSet() refreshes the object
            ->forceSet('body', 'new body')
            ->save()
        ;

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('new body', $post->getBody());
    }

    /**
     * @test
     */
    public function force_set_all_solves_the_auto_refresh_problem(): void
    {
        $post = PostFactory::new()->create(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->enableAutoRefresh()
            ->forceSetAll([
                'title' => 'new title',
                'body' => 'new body',
            ])
            ->save()
        ;

        $this->assertSame('new title', $post->getTitle());
        $this->assertSame('new body', $post->getBody());
    }

    /**
     * @test
     */
    public function without_auto_refresh_solves_the_auto_refresh_problem(): void
    {
        $post = PostFactory::new()->create(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->enableAutoRefresh()
            ->withoutAutoRefresh(static function(Proxy $proxy) {
                $proxy
                    ->forceSet('title', 'new title')
                    ->forceSet('body', 'new body')
                ;
            })
            ->save()
        ;

        $this->assertSame('new title', $post->getTitle());
        $this->assertSame('new body', $post->getBody());
    }

    /**
     * @test
     */
    public function without_auto_refresh_does_not_enable_auto_refresh_if_it_was_disabled_originally(): void
    {
        $post = PostFactory::new()->create(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->withoutAutoRefresh(static function(Proxy $proxy) {
                $proxy
                    ->forceSet('title', 'new title')
                    ->forceSet('body', 'new body')
                ;
            })
            ->forceSet('title', 'another new title')
            ->forceSet('body', 'another new body')
            ->save()
        ;

        $this->assertSame('another new title', $post->getTitle());
        $this->assertSame('another new body', $post->getBody());
    }

    /**
     * @test
     */
    public function without_auto_refresh_re_enables_if_enabled_originally(): void
    {
        $post = PostFactory::new()->create(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->enableAutoRefresh()
            ->withoutAutoRefresh(static function(Proxy $proxy) {
                $proxy
                    ->forceSet('title', 'new title')
                    ->forceSet('body', 'new body')
                ;
            })
            ->save()
            ->forceSet('title', 'another new title') // will not be saved because the following ->forceSet() refreshes the object
            ->forceSet('body', 'another new body')
            ->save()
        ;

        $this->assertSame('new title', $post->getTitle());
        $this->assertSame('another new body', $post->getBody());
    }
}
