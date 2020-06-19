<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\UnitTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProxyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function can_force_get_and_set_non_public_properties(): void
    {
        $proxy = new Proxy(new Category(), $this->manager);

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
        }, $this->manager);

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

        (new Proxy(new Category(), $this->manager))->refresh();
    }

    /**
     * @test
     */
    public function saving_unpersisted_proxy_changes_it_to_a_persisted_proxy(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        $this->manager->setManagerRegistry($registry);

        $category = new Proxy(new Category(), $this->manager);

        $this->assertFalse($category->isPersisted());

        $category->save();

        $this->assertTrue($category->isPersisted());
    }
}
