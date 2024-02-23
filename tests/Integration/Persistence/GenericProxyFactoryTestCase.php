<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class GenericProxyFactoryTestCase extends GenericFactoryTestCase
{
    /**
     * @test
     */
    public function can_update_and_delete_via_proxy(): void
    {
        $this->factory()->repository()->assert()->empty();

        $object = $this->factory()->create();

        $this->assertNotNull($object->id);
        $this->assertSame('default1', $object->getProp1());
        $this->assertSame('default1', $object->_refresh()->getProp1());

        $this->factory()->repository()->assert()
            ->count(1)
            ->exists(['prop1' => 'default1'])
            ->notExists(['prop1' => 'invalid'])
        ;

        $this->assertSame($object->id, $this->factory()->first()->id);
        $this->assertSame($object->id, $this->factory()->last()->id);

        $object->setProp1('new value');
        $object->_save();

        $this->assertSame('new value', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'new value']);

        $object->_delete();

        $this->factory()->repository()->assert()->empty();
    }

    /**
     * @test
     */
    public function can_disable_persisting_by_factory_and_save_proxy(): void
    {
        $this->factory()->repository()->assert()->empty();

        $object = $this->factory()->withoutPersisting()->create()->_disableAutoRefresh();

        $this->assertNull($object->id);
        $this->assertSame('default1', $object->getProp1());

        $this->factory()->repository()->assert()->empty();

        $object->_save();

        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);
    }

    /**
     * @test
     */
    public function can_disable_and_enable_proxy_auto_refreshing(): void
    {
        $object = $this->factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);

        $object->_disableAutoRefresh();
        $object->setProp1('new');
        $object->setProp1('new 2');
        $object->_enableAutoRefresh();
        $object->_save();

        $this->assertSame('new 2', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'new 2']);
    }

    /**
     * @test
     */
    public function can_disable_and_enable_proxy_auto_refreshing_with_callback(): void
    {
        $object = $this->factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);

        $object->_withoutAutoRefresh(function(GenericModel&Proxy $object) {
            $object->setProp1('new');
            $object->setProp1('new 2');
            $object->_save();
        });

        $this->assertSame('new 2', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'new 2']);
    }

    /**
     * @test
     */
    public function can_manually_refresh_via_proxy(): void
    {
        $object = $this->factory()->create()->_disableAutoRefresh();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);

        self::ensureKernelShutdown();

        // modify and save title "externally"
        $ext = $this->factory()::first();
        $ext->setProp1('external');
        $ext->_save();

        self::ensureKernelShutdown();

        $object->_enableAutoRefresh();

        // "calling method" triggers auto-refresh
        $this->assertSame('external', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'external']);
    }

    /**
     * @test
     */
    public function proxy_auto_refreshes(): void
    {
        $object = $this->factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);

        self::ensureKernelShutdown();

        // modify and save title "externally"
        $ext = $this->factory()::first();
        $ext->setProp1('external');
        $ext->_save();

        self::ensureKernelShutdown();

        // "calling method" triggers auto-refresh
        $this->assertSame('external', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'external']);
    }

    /**
     * @test
     */
    public function cannot_auto_refresh_proxy_if_changes(): void
    {
        $object = $this->factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);

        $object->setProp1('new');

        try {
            $object->setProp1('new 1');
        } catch (\RuntimeException) {
            $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);
            $object->_save();
            $this->assertSame('new', $object->getProp1());
            $this->factory()->repository()->assert()->exists(['prop1' => 'new']);

            return;
        }

        $this->fail('Exception not thrown');
    }

    /**
     * @test
     */
    public function can_access_repository_from_proxy(): void
    {
        $object = $this->factory()::createOne();

        $object = $object->_repository()->findOneBy(['prop1' => 'default1']);

        $this->assertInstanceOf($this->factory()::class(), $object);
    }

    /**
     * @test
     */
    public function can_force_set_and_get_proxy(): void
    {
        $object = $this->factory()::createOne();

        $this->assertSame('default1', $object->_get('prop1'));

        $object->_set('prop1', 'new value')->_save();

        $object->_repository()->assert()->exists(['prop1' => 'new value']);
    }

    /**
     * @test
     */
    public function can_get_real_object_even_if_modified(): void
    {
        $object = $this->factory()->create();
        $object->setProp1('foo');

        self::assertInstanceOf(GenericModel::class, $real = $object->_real());
        self::assertSame('foo', $real->getProp1());

    }

    /**
     * @return PersistentProxyObjectFactory<GenericModel>
     */
    abstract protected function factory(): PersistentProxyObjectFactory; // @phpstan-ignore-line
}
