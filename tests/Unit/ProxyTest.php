<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\BaseFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProxyTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    public function can_force_get_and_set_non_public_properties(): void
    {
        $proxy = new Proxy(new Category());

        $this->assertNull($proxy->forceGet('name'));

        $proxy->forceSet('name', 'foo');

        $this->assertSame('foo', $proxy->forceGet('name'));
    }

    /**
     * @test
     */
    public function can_access_wrapped_objects_properties(): void
    {
        $proxy = new Proxy(new class() {
            public $property;
        });

        $this->assertFalse(isset($proxy->property));

        $proxy->property = 'foo';

        $this->assertSame('foo', $proxy->property);

        $this->assertTrue(isset($proxy->property));

        unset($proxy->property);

        $this->assertFalse(isset($proxy->property));
    }

    /**
     * @test
     */
    public function cannot_refresh_unpersisted_proxy(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot refresh unpersisted object (Zenstruck\Foundry\Tests\Fixtures\Entity\Category).');

        (new Proxy(new Category()))->refresh();
    }

    /**
     * @test
     */
    public function saving_unpersisted_proxy_changes_it_to_a_persisted_proxy(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        BaseFactory::configuration()->disableDefaultProxyAutoRefresh();
        PersistentObjectFactory::persistenceManager()->setManagerRegistry($registry);

        $category = new Proxy(new Category());

        $this->assertFalse($category->isPersisted());

        $category->save();

        $this->assertTrue($category->isPersisted());
    }

    /**
     * @test
     */
    public function can_use_without_auto_refresh_with_proxy_or_object_typehint(): void
    {
        $proxy = new Proxy(new Category());
        $calls = 0;

        $proxy
            ->withoutAutoRefresh(static function(Proxy $proxy) use (&$calls): void {
                ++$calls;
            })
            ->withoutAutoRefresh(static function(Category $category) use (&$calls): void {
                ++$calls;
            })
            ->withoutAutoRefresh(function($proxy) use (&$calls): void {
                $this->assertInstanceOf(Proxy::class, $proxy);
                ++$calls;
            })
            ->withoutAutoRefresh(static function() use (&$calls): void {
                ++$calls;
            })
            ->withoutAutoRefresh(fn(Proxy $proxy) => $this->functionWithProxy($proxy))
            ->withoutAutoRefresh(fn(Category $category) => $this->functionWithObject($category))
            ->withoutAutoRefresh(fn($proxy) => $this->functionWithNoTypeHint($proxy))
            ->withoutAutoRefresh(static fn(Proxy $proxy) => self::staticFunctionWithProxy($proxy))
            ->withoutAutoRefresh(static fn(Category $category) => self::staticFunctionWithObject($category))
            ->withoutAutoRefresh(static fn($proxy) => self::staticFunctionWithNoTypeHint($proxy))
        ;

        $this->assertSame(4, $calls);
    }

    public function functionWithProxy(Proxy $proxy): void
    {
        $this->assertInstanceOf(Category::class, $proxy->object());
    }

    public function functionWithObject(Category $category): void
    {
        $this->assertInstanceOf(Category::class, $category);
    }

    public function functionWithNoTypeHint($proxy): void
    {
        $this->assertInstanceOf(Proxy::class, $proxy);
        $this->assertInstanceOf(Category::class, $proxy->object());
    }

    public static function staticFunctionWithProxy(Proxy $proxy): void
    {
        self::assertInstanceOf(Category::class, $proxy->object());
    }

    public static function staticFunctionWithObject(Category $category): void
    {
        self::assertInstanceOf(Category::class, $category);
    }

    public static function staticFunctionWithNoTypeHint($proxy): void
    {
        self::assertInstanceOf(Proxy::class, $proxy);
        self::assertInstanceOf(Category::class, $proxy->object());
    }
}
