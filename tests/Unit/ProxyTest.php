<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Factory;
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

        Factory::configuration()->setManagerRegistry($registry);

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
            ->withoutAutoRefresh(static function(Proxy $proxy) use (&$calls) {
                ++$calls;
            })
            ->withoutAutoRefresh(static function(Category $category) use (&$calls) {
                ++$calls;
            })
            ->withoutAutoRefresh(function($proxy) use (&$calls) {
                $this->assertInstanceOf(Proxy::class, $proxy);
                ++$calls;
            })
            ->withoutAutoRefresh(static function() use (&$calls) {
                ++$calls;
            })
            ->withoutAutoRefresh([$this, 'functionWithProxy'])
            ->withoutAutoRefresh([$this, 'functionWithObject'])
            ->withoutAutoRefresh([$this, 'functionWithNoTypeHint'])
            ->withoutAutoRefresh([self::class, 'staticFunctionWithProxy'])
            ->withoutAutoRefresh([self::class, 'staticFunctionWithObject'])
            ->withoutAutoRefresh([self::class, 'staticFunctionWithNoTypeHint'])
        ;

        $this->assertSame(4, $calls);
    }

    public function functionWithProxy(Proxy $proxy)
    {
        $this->assertInstanceOf(Category::class, $proxy->object());
    }

    public function functionWithObject(Category $category)
    {
        $this->assertInstanceOf(Category::class, $category);
    }

    public function functionWithNoTypeHint($proxy)
    {
        $this->assertInstanceOf(Proxy::class, $proxy);
        $this->assertInstanceOf(Category::class, $proxy->object());
    }

    public static function staticFunctionWithProxy(Proxy $proxy)
    {
        self::assertInstanceOf(Category::class, $proxy->object());
    }

    public static function staticFunctionWithObject(Category $category)
    {
        self::assertInstanceOf(Category::class, $category);
    }

    public static function staticFunctionWithNoTypeHint($proxy)
    {
        self::assertInstanceOf(Proxy::class, $proxy);
        self::assertInstanceOf(Category::class, $proxy->object());
    }
}
