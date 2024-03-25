<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional;

use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Assert;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ProxyTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     * @group legacy
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
     * @group legacy
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
     * @group legacy
     */
    public function can_remove_and_assert_not_persisted(): void
    {
        $post = $this->postFactoryClass()::createOne();

        $post->_delete();

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
     * @group legacy
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

        self::getContainer()->get($this->registryServiceId())->getManager()->clear();

        $this->assertSame('my title', $post->_refresh()->getTitle());
    }

    /**
     * @test
     */
    public function exception_thrown_if_trying_to_refresh_deleted_object(): void
    {
        $postFactoryClass = $this->postFactoryClass();

        $post = $postFactoryClass::createOne();

        self::getContainer()->get($this->registryServiceId())->getManager()->clear();

        $postFactoryClass::repository()->truncate();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The object no longer exists.');

        $post->_refresh();
    }

    /**
     * @test
     */
    public function can_force_set_and_save(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'old title']);

        $post->_repository()->assert()->notExists(['title' => 'new title']);

        $post->_set('title', 'new title')->_save();

        $post->_repository()->assert()->exists(['title' => 'new title']);
    }

    /**
     * @test
     */
    public function can_force_set_multiple_fields(): void
    {
        /** @var Proxy<Post> $post */
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body']);
        $post->_disableAutoRefresh();

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->_set('title', 'new title')
            ->_set('body', 'new body')
            ->_save()
        ;

        $this->assertSame('new title', $post->getTitle());
        $this->assertSame('new body', $post->getBody());
    }

    /**
     * @test
     */
    public function can_autorefresh_between_kernel_boots(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body'])
            ->_enableAutoRefresh()
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
     * @group legacy
     */
    public function force_set_all_solves_the_auto_refresh_problem(): void
    {
        $post = $this->postFactoryClass()::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->_enableAutoRefresh()
            ->forceSetAll([
                'title' => 'new title',
                'body' => 'new body',
            ])
            ->_save()
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
            ->_enableAutoRefresh()
            ->_withoutAutoRefresh(static function(Proxy $proxy): void {
                $proxy
                    ->_set('title', 'new title')
                    ->_set('body', 'new body')
                ;
            })
            ->_save()
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
        $post->_disableAutoRefresh();

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->_withoutAutoRefresh(static function(Proxy $proxy): void {
                $proxy
                    ->_set('title', 'new title')
                    ->_set('body', 'new body')
                ;
            })
            ->_set('title', 'another new title')
            ->_set('body', 'another new body')
            ->_save()
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
        $post->_disableAutoRefresh();

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->_withoutAutoRefresh(static function(Proxy $proxy): void {
                $proxy
                    ->_set('title', 'new title')
                    ->_set('body', 'new body')
                ;
            })
            ->_save()
            ->_set('title', 'another new title')
            ->_set('body', 'another new body')
            ->_save()
        ;

        $this->assertSame('another new title', $post->getTitle());
        $this->assertSame('another new body', $post->getBody());
    }

    /** @return class-string<PersistentProxyObjectFactory> */
    abstract protected function postFactoryClass(): string;

    abstract protected function postClass(): string;

    abstract protected function registryServiceId(): string;
}
