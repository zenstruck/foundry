<?php

namespace Zenstruck\Foundry\Tests\Functional;

use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Assert;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ProxyTest extends KernelTestCase
{
    use ContainerBC, Factories, ResetDatabase;

    /**
     * @test
     */
    public function can_assert_persisted(): void
    {
        $this->postFactoryClass()::createOne()->assertPersisted();

        Assert::that(function(): void { $this->postFactoryClass()::new()->withoutPersisting()->create()->assertPersisted(); })
            ->throws(AssertionFailedError::class, \sprintf('%s is not persisted.', $this->postClass()))
        ;
    }

    /**
     * @test
     */
    public function can_assert_not_persisted(): void
    {
        $this->postFactoryClass()::new()->withoutPersisting()->create()->assertNotPersisted();

        Assert::that(function(): void { $this->postFactoryClass()::createOne()->assertNotPersisted(); })
            ->throws(AssertionFailedError::class, \sprintf('%s is persisted but it should not be.', $this->postClass()))
        ;
    }

    /**
     * @test
     */
    public function can_remove_and_assert_not_persisted(): void
    {
        $post = $this->postFactoryClass()::createOne();

        $post->remove();

        $post->assertNotPersisted();
    }

    /**
     * @test
     */
    public function functions_are_passed_to_wrapped_object(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'my title']);

        $this->assertSame('my title', $post->getTitle());
    }

    /**
     * @test
     */
    public function can_convert_to_string_if_wrapped_object_can(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'my title']);

        $this->assertSame('my title', (string) $post);
    }

    /**
     * @test
     */
    public function can_refetch_object_if_object_manager_has_been_cleared(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'my title']);

        self::container()->get($this->registryServiceId())->getManager()->clear();

        $this->assertSame('my title', $post->refresh()->getTitle());
    }

    /**
     * @test
     */
    public function exception_thrown_if_trying_to_refresh_deleted_object(): void
    {
        $postFactoryClass = $this->postFactoryClass();

        $post = $postFactoryClass::createOne();

        self::container()->get($this->registryServiceId())->getManager()->clear();

        $postFactoryClass::repository()->truncate();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The object no longer exists.');

        $post->refresh();
    }

    /**
     * @test
     */
    public function can_force_set_and_save(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'old title']);

        $post->repository()->assert()->notExists(['title' => 'new title']);

        $post->forceSet('title', 'new title')->save();

        $post->repository()->assert()->exists(['title' => 'new title']);
    }

    /**
     * @test
     */
    public function can_force_set_multiple_fields(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body']);

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
    public function exception_thrown_if_trying_to_autorefresh_object_with_unsaved_changes(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body'])
            ->enableAutoRefresh()
        ;

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->enableAutoRefresh()
            ->forceSet('title', 'new title')
        ;

        $this->expectException(\RuntimeException::class);

        // exception thrown because of "unsaved changes" to $post from above
        $post->forceSet('body', 'new body');
    }

    /**
     * @test
     */
    public function can_autorefresh_between_kernel_boots(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body'])
            ->enableAutoRefresh()
        ;

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        // reboot kernel
        self::ensureKernelShutdown();
        self::bootKernel();

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());
    }

    /**
     * @test
     */
    public function force_set_all_solves_the_auto_refresh_problem(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body']);

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
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->enableAutoRefresh()
            ->withoutAutoRefresh(static function(Proxy $proxy): void {
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
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->withoutAutoRefresh(static function(Proxy $proxy): void {
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
    public function without_auto_refresh_keeps_disabled_if_originally_disabled(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->withoutAutoRefresh(static function(Proxy $proxy): void {
                $proxy
                    ->forceSet('title', 'new title')
                    ->forceSet('body', 'new body')
                ;
            })
            ->save()
            ->forceSet('title', 'another new title')
            ->forceSet('body', 'another new body')
            ->save()
        ;

        $this->assertSame('another new title', $post->getTitle());
        $this->assertSame('another new body', $post->getBody());
    }

    abstract protected function postFactoryClass(): string;

    abstract protected function postClass(): string;

    abstract protected function registryServiceId(): string;
}
