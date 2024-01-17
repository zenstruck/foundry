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
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Proxy as ProxyObject;
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
        $proxy = new ProxyObject(new Category());

        $this->assertNull($proxy->_get('name'));

        $proxy->_set('name', 'foo');

        $this->assertSame('foo', $proxy->_get('name'));
    }

    /**
     * @test
     * @group legacy
     */
    public function can_access_wrapped_objects_properties(): void
    {
        $proxy = new ProxyObject(new class() {
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

        (new ProxyObject(new Category()))->_refresh();
    }

    /**
     * @test
     * @group legacy
     */
    public function saving_unpersisted_proxy_changes_it_to_a_persisted_proxy(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        Factory::configuration()->setManagerRegistry($registry)->enableDefaultProxyAutoRefresh();

        $category = new ProxyObject(new Category());

        $this->assertFalse($category->isPersisted());

        $category->_save();

        $this->assertTrue($category->isPersisted());
    }

    /**
     * @test
     */
    public function can_use_without_auto_refresh_with_proxy_or_object_typehint(): void
    {
        $proxy = new ProxyObject(new Category());
        $calls = 0;

        $proxy
            ->_withoutAutoRefresh(static function(Proxy $proxy) use (&$calls): void {
                ++$calls;
            })
            ->_withoutAutoRefresh(static function(Category $category) use (&$calls): void {
                ++$calls;
            })
            ->_withoutAutoRefresh(function($proxy) use (&$calls): void {
                $this->assertInstanceOf(Proxy::class, $proxy);
                ++$calls;
            })
            ->_withoutAutoRefresh(static function() use (&$calls): void {
                ++$calls;
            })
            ->_withoutAutoRefresh(fn(Proxy $proxy) => $this->functionWithProxy($proxy))
            ->_withoutAutoRefresh(fn(Category $category) => $this->functionWithObject($category))
            ->_withoutAutoRefresh(fn($proxy) => $this->functionWithNoTypeHint($proxy))
            ->_withoutAutoRefresh(static fn(Proxy $proxy) => self::staticFunctionWithProxy($proxy))
            ->_withoutAutoRefresh(static fn(Category $category) => self::staticFunctionWithObject($category))
            ->_withoutAutoRefresh(static fn($proxy) => self::staticFunctionWithNoTypeHint($proxy))
        ;

        $this->assertSame(4, $calls);
    }

    /**
     * @test
     */
    public function can_use_new_class_as_legacy_one(): void
    {
        $proxy = new ProxyObject(new Category());

        self::assertInstanceOf(ProxyObject::class, $proxy);
        self::assertInstanceOf(Proxy::class, $proxy);
    }

    public function functionWithProxy(Proxy $proxy): void
    {
        $this->assertInstanceOf(Category::class, $proxy->_real());
    }

    public function functionWithObject(Category $category): void
    {
        $this->assertInstanceOf(Category::class, $category);
    }

    public function functionWithNoTypeHint($proxy): void
    {
        $this->assertInstanceOf(Proxy::class, $proxy);
        $this->assertInstanceOf(Category::class, $proxy->_real());
    }

    public static function staticFunctionWithProxy(Proxy $proxy): void
    {
        self::assertInstanceOf(Category::class, $proxy->_real());
    }

    public static function staticFunctionWithObject(Category $category): void
    {
        self::assertInstanceOf(Category::class, $category);
    }

    public static function staticFunctionWithNoTypeHint($proxy): void
    {
        self::assertInstanceOf(Proxy::class, $proxy);
        self::assertInstanceOf(Category::class, $proxy->_real());
    }
}
