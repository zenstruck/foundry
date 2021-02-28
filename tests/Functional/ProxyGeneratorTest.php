<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\AnonymousFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithProxyGenerator;
use function Zenstruck\Foundry\disable_autorefresh;
use function Zenstruck\Foundry\force_set;
use function Zenstruck\Foundry\force_set_all;
use function Zenstruck\Foundry\without_autorefresh;

/**
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
final class ProxyGeneratorTest extends ProxyTest
{
    protected static $POST_FACTORY = PostFactoryWithProxyGenerator::class;

    /**
     * @test
     */
    public function can_assert_persisted(): void
    {
        $this->markTestSkipped('assertPersisted() is not supported');
    }

    /**
     * @test
     */
    public function can_remove_and_assert_not_persisted(): void
    {
        $this->markTestSkipped('assertNotPersisted() is not supported');
    }

    /**
     * @test
     */
    public function can_refetch_object_if_object_manager_has_been_cleared(): void
    {
        $this->markTestSkipped('refresh() is not supported');
    }

    /**
     * @test
     */
    public function exception_thrown_if_trying_to_refresh_deleted_object(): void
    {
        $this->markTestSkipped('refresh() is not supported');
    }

    /**
     * @test
     */
    public function can_force_set_and_save(): void
    {
        $post = static::$POST_FACTORY::createOne(['title' => 'old title']);

        static::$POST_FACTORY::assert()->notExists(['title' => 'new title']);

        force_set($post, 'title', 'new title');
        static::$POST_FACTORY::save($post);

        static::$POST_FACTORY::assert()->exists(['title' => 'new title']);
    }

    /**
     * @test
     */
    public function can_force_set_multiple_fields(): void
    {
        $post = static::$POST_FACTORY::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        force_set($post, 'title', 'new title');
        force_set($post, 'body', 'new body');
        static::$POST_FACTORY::save($post);

        $this->assertSame('new title', $post->getTitle());
        $this->assertSame('new body', $post->getBody());
    }

    /**
     * @test
     */
    public function exception_thrown_if_trying_to_autorefresh_object_with_unsaved_changes(): void
    {
        $post = static::$POST_FACTORY::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        $post->setTitle('new title');

        $this->expectException(\RuntimeException::class);

        $post->setBody('new body');
    }

    /**
     * @test
     */
    public function can_autorefresh_between_kernel_boots(): void
    {
        $this->markTestSkipped('not supported');

        $post = static::$POST_FACTORY::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        // reboot kernel
        static::ensureKernelShutdown();
        static::bootKernel();

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());
    }

    /**
     * @test
     */
    public function can_autorefresh_entity_with_embedded_object(): void
    {
        $contactFactory = AnonymousFactory::new(Contact::class)->withProxyGenerator();
        $contact = $contactFactory->create(['name' => 'john']);

        $this->assertSame('john', $contact->getName());

        // I discovered when autorefreshing the second time, the embedded
        // object is included in the changeset when using UOW::recomputeSingleEntityChangeSet().
        // Changing to UOW::computeChangeSet() fixes this.
        $this->assertSame('john', $contact->getName());
        $this->assertNull($contact->getAddress()->getValue());

        $contact->getAddress()->setValue('address');
        $contactFactory->save($contact);

        $this->assertSame('address', $contact->getAddress()->getValue());

        static::ensureKernelShutdown();
        static::bootKernel();

        $this->assertSame('address', $contact->getAddress()->getValue());
    }

    /**
     * @test
     */
    public function force_set_all_solves_the_auto_refresh_problem(): void
    {
        $post = static::$POST_FACTORY::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        force_set_all($post, [
            'title' => 'new title',
            'body' => 'new body',
        ]);
        static::$POST_FACTORY::save($post);

        $this->assertSame('new title', $post->getTitle());
        $this->assertSame('new body', $post->getBody());
    }

    /**
     * @test
     */
    public function without_auto_refresh_solves_the_auto_refresh_problem(): void
    {
        $post = static::$POST_FACTORY::createOne(['title' => 'old title', 'body' => 'old body']);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        without_autorefresh($post, static function(Post $post) {
            $post->setTitle('new title');
            $post->setBody('new body');
        });
        static::$POST_FACTORY::save($post);

        $this->assertSame('new title', $post->getTitle());
        $this->assertSame('new body', $post->getBody());
    }

    /**
     * @test
     */
    public function without_auto_refresh_does_not_enable_auto_refresh_if_it_was_disabled_originally(): void
    {
        $post = static::$POST_FACTORY::createOne(['title' => 'old title', 'body' => 'old body']);

        disable_autorefresh($post);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        without_autorefresh($post, static function(Post $post) {
            $post->setTitle('new title');
            $post->setBody('new body');
        });

        $post->setTitle('another new title');
        $post->setBody('another new body');

        static::$POST_FACTORY::save($post);

        $this->assertSame('another new title', $post->getTitle());
        $this->assertSame('another new body', $post->getBody());
    }

    /**
     * @test
     */
    public function without_auto_refresh_keeps_disabled_if_originally_disabled(): void
    {
        $post = static::$POST_FACTORY::createOne(['title' => 'old title', 'body' => 'old body']);

        disable_autorefresh($post);

        $this->assertSame('old title', $post->getTitle());
        $this->assertSame('old body', $post->getBody());

        without_autorefresh($post, static function(Post $post) {
            $post->setTitle('new title');
            $post->setBody('new body');
        });

        static::$POST_FACTORY::save($post);

        $post->setTitle('another new title');
        $post->setBody('another new body');

        static::$POST_FACTORY::save($post);

        $this->assertSame('another new title', $post->getTitle());
        $this->assertSame('another new body', $post->getBody());
    }
}
