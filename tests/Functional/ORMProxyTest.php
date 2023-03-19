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

use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use function Zenstruck\Foundry\anonymous;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ORMProxyTest extends ProxyTest
{
    protected function setUp(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
    }

    /**
     * @test
     */
    public function cannot_convert_to_string_if_underlying_object_cant(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('Proxied object "%s" cannot be converted to a string.', Category::class));

        (string) CategoryFactory::createOne();
    }

    /**
     * @test
     */
    public function can_autorefresh_entity_with_embedded_object(): void
    {
        $contact = anonymous(Contact::class)->create(['name' => 'john'])
            ->enableAutoRefresh()
        ;

        $this->assertSame('john', $contact->getName());

        // I discovered when autorefreshing the second time, the embedded
        // object is included in the changeset when using UOW::recomputeSingleEntityChangeSet().
        // Changing to UOW::computeChangeSet() fixes this.
        $this->assertSame('john', $contact->getName());
        $this->assertSame('some address', $contact->getAddress()->getValue());

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
