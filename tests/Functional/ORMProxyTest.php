<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\AnonymousFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ORMProxyTest extends ProxyTest
{
    protected function setUp(): void
    {
        if (false === \getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
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

    protected function postFactoryClass(): string
    {
        return PostFactory::class;
    }

    protected function postClass(): string
    {
        return Post::class;
    }

    protected function registryServiceId(): string
    {
        return 'doctrine';
    }
}
