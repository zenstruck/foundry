<?php

namespace Zenstruck\Foundry\Tests\Functional;

use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Assert;
use Zenstruck\Foundry\AnonymousFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProxyTest extends KernelTestCase
{
    use ContainerBC, Factories, ResetDatabase;

    /**
     * @test
     */
    public function can_assert_persisted(): void
    {
        PostFactory::createOne()->assertPersisted();

        Assert::that(function() { PostFactory::new()->withoutPersisting()->create()->assertPersisted(); })
            ->throws(AssertionFailedError::class, \sprintf('%s is not persisted.', Post::class))
        ;
    }

    /**
     * @test
     */
    public function can_assert_not_persisted(): void
    {
        PostFactory::new()->withoutPersisting()->create()->assertNotPersisted();

        Assert::that(function() { PostFactory::createOne()->assertNotPersisted(); })
            ->throws(AssertionFailedError::class, \sprintf('%s is persisted but it should not be.', Post::class))
        ;
    }

    /**
     * @test
     */
    public function can_remove_and_assert_not_persisted(): void
    {
        $post = PostFactory::createOne();

        $post->remove();

        $post->assertNotPersisted();
    }

    /**
     * @test
     */
    public function functions_are_passed_to_wrapped_object(): void
    {
        $post = PostFactory::createOne(['title' => 'my title']);

        $this->assertSame('my title', $post->getTitle());
    }

    /**
     * @test
     */
    public function can_convert_to_string_if_wrapped_object_can(): void
    {
        $post = PostFactory::createOne(['title' => 'my title']);

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

        (string) CategoryFactory::createOne();
    }

    /**
     * @test
     * @requires PHP < 7.4
     */
    public function on_php_versions_less_than_7_4_if_underlying_object_is_missing_to_string_proxy_to_string_returns_note(): void
    {
        $this->assertSame('(no __toString)', (string) CategoryFactory::createOne());
    }

    /**
     * @test
     */
    public function can_refetch_object_if_object_manager_has_been_cleared(): void
    {
        $post = PostFactory::createOne(['title' => 'my title']);

        self::container()->get('doctrine')->getManager()->clear();

        $this->assertSame('my title', $post->refresh()->getTitle());
    }

    /**
     * @test
     */
    public function exception_thrown_if_trying_to_refresh_deleted_object(): void
    {
        $post = PostFactory::createOne();

        self::container()->get('doctrine')->getManager()->clear();

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
        $post = PostFactory::createOne(['title' => 'old title']);

        $post->repository()->assert()->notExists(['title' => 'new title']);

        $post->forceSet('title', 'new title')->save();

        $post->repository()->assert()->exists(['title' => 'new title']);
    }

    /**
     * @test
     */
    public function can_force_set_multiple_fields(): void
    {
        $post = PostFactory::createOne(['title' => 'old title', 'body' => 'old body']);

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
        $post = PostFactory::createOne(['title' => 'old title', 'body' => 'old body'])
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
        $post = PostFactory::createOne(['title' => 'old title', 'body' => 'old body'])
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
    public function can_autorefresh_entity_with_embedded_object(): void
    {
        $contact = AnonymousFactory::new(Contact::class)->create(['name' => 'john'])
            ->enableAutoRefresh()
        ;

        $this->assertSame('john', $contact->getName());

        // I discovered when autorefreshing the second time, the embedded
        // object is included in the changeset when using UOW::recomputeSingleEntityChangeSet().
        // Changing to UOW::computeChangeSet() fixes this.
        $this->assertSame('john', $contact->getName());
        $this->assertNull($contact->getAddress()->getValue());

        $contact->getAddress()->setValue('address');
        $contact->save();

        $this->assertSame('address', $contact->getAddress()->getValue());

        self::ensureKernelShutdown();
        self::bootKernel();

        $this->assertSame('address', $contact->getAddress()->getValue());
    }

    /**
     * @test
     */
    public function force_set_all_solves_the_auto_refresh_problem(): void
    {
        $post = PostFactory::createOne(['title' => 'old title', 'body' => 'old body']);

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
        $post = PostFactory::createOne(['title' => 'old title', 'body' => 'old body']);

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
        $post = PostFactory::createOne(['title' => 'old title', 'body' => 'old body']);

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
    public function without_auto_refresh_keeps_disabled_if_originally_disabled(): void
    {
        $post = PostFactory::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post
            ->withoutAutoRefresh(static function(Proxy $proxy) {
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
}
