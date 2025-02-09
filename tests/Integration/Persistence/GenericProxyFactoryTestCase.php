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

use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\EntityWithReadonly\EntityWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

use function Zenstruck\Foundry\factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class GenericProxyFactoryTestCase extends GenericFactoryTestCase
{
    /**
     * @test
     */
    #[Test]
    public function can_update_and_delete_via_proxy(): void
    {
        static::factory()->repository()->assert()->empty();

        $object = static::factory()->create();

        $this->assertNotNull($object->id);
        $this->assertSame('default1', $object->getProp1());
        $this->assertSame('default1', $object->_refresh()->getProp1());

        static::factory()->repository()->assert()
            ->count(1)
            ->exists(['prop1' => 'default1'])
            ->notExists(['prop1' => 'invalid'])
        ;

        $this->assertSame($object->id, static::factory()->first()->id);
        $this->assertSame($object->id, static::factory()->last()->id);

        $object->setProp1('new value');
        $object->_save();

        $this->assertSame('new value', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'new value']);

        $object->_delete();

        static::factory()->repository()->assert()->empty();
    }

    /**
     * @test
     */
    #[Test]
    public function can_disable_persisting_by_factory_and_save_proxy(): void
    {
        static::factory()->repository()->assert()->empty();

        $object = static::factory()->withoutPersisting()->create()->_disableAutoRefresh();

        $this->assertNull($object->id);
        $this->assertSame('default1', $object->getProp1());

        static::factory()->repository()->assert()->empty();

        $object->_save();

        static::factory()->repository()->assert()->exists(['prop1' => 'default1']);
    }

    /**
     * @test
     */
    #[Test]
    public function can_disable_and_enable_proxy_auto_refreshing(): void
    {
        $object = static::factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'default1']);

        $object->_disableAutoRefresh();
        $object->setProp1('new');
        $object->setProp1('new 2');
        $object->_enableAutoRefresh();
        $object->_save();

        $this->assertSame('new 2', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'new 2']);
    }

    /**
     * @test
     */
    #[Test]
    public function can_disable_and_enable_proxy_auto_refreshing_with_callback(): void
    {
        $object = static::factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'default1']);

        $object->_withoutAutoRefresh(function(GenericModel&Proxy $object) {
            $object->setProp1('new');
            $object->setProp1('new 2');
            $object->_save();
        });

        $this->assertSame('new 2', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'new 2']);
    }

    /**
     * @test
     */
    #[Test]
    public function can_manually_refresh_via_proxy(): void
    {
        $object = static::factory()->create()->_disableAutoRefresh();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'default1']);

        self::ensureKernelShutdown();

        // modify and save title "externally"
        $ext = static::factory()::first();
        $ext->setProp1('external');
        $ext->_save();

        self::ensureKernelShutdown();

        $object->_enableAutoRefresh();

        // "calling method" triggers auto-refresh
        $this->assertSame('external', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'external']);
    }

    /**
     * @test
     */
    #[Test]
    public function proxy_auto_refreshes(): void
    {
        $object = static::factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'default1']);

        self::ensureKernelShutdown();

        // modify and save title "externally"
        $ext = static::factory()::first();
        $ext->setProp1('external');
        $ext->_save();

        self::ensureKernelShutdown();

        // "calling method" triggers auto-refresh
        $this->assertSame('external', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'external']);
    }

    /**
     * @test
     */
    #[Test]
    public function cannot_auto_refresh_proxy_if_changes(): void
    {
        $object = static::factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        static::factory()->repository()->assert()->exists(['prop1' => 'default1']);

        $object->setProp1('new');

        try {
            $object->setProp1('new 1');
        } catch (\RuntimeException) {
            static::factory()->repository()->assert()->exists(['prop1' => 'default1']);
            $object->_save();
            $this->assertSame('new', $object->getProp1());
            static::factory()->repository()->assert()->exists(['prop1' => 'new']);

            return;
        }

        $this->fail('Exception not thrown');
    }

    /**
     * @test
     */
    #[Test]
    public function can_access_repository_from_proxy(): void
    {
        $object = static::factory()::createOne();

        $object = $object->_repository()->findOneBy(['prop1' => 'default1']);

        $this->assertInstanceOf(static::factory()::class(), $object);
    }

    /**
     * @test
     */
    #[Test]
    public function can_force_set_and_get_proxy(): void
    {
        $object = static::factory()::createOne();

        $this->assertSame('default1', $object->_get('prop1'));

        $object->_set('prop1', 'new value')->_save();

        $object->_repository()->assert()->exists(['prop1' => 'new value']);
    }

    /**
     * @test
     */
    #[Test]
    public function can_get_real_object_even_if_modified(): void
    {
        $object = static::factory()->create();
        $object->setProp1('foo');

        self::assertInstanceOf(GenericModel::class, $real = $object->_real());
        self::assertSame('foo', $real->getProp1());
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_object_with_readonly_properties(): void
    {
        $factory = $this->objectWithReadonlyFactory();

        $objectWithReadOnly = $factory::createOne([
            'prop' => 1,
            'embedded' => factory(Embeddable::class, ['prop1' => 'value1']),
            'date' => new \DateTimeImmutable(),
        ]);

        $objectWithReadOnly->_refresh();

        $factory::assert()->count(1);
    }

    /**
     * @test
     */
    #[Test]
    public function can_delete_proxified_object_and_still_access_its_methods(): void
    {
        $object = static::factory()->create();
        $object->_delete();

        $this->assertSame('default1', $object->getProp1());
    }

    /**
     * @test
     */
    #[Test]
    public function can_use_after_persist_with_attributes_added_in_before_instantiate(): void
    {
        $value = 'value set with before instantiate';
        $object = $this->factory()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra('extra'))
            ->beforeInstantiate(function(array $attributes) use ($value) {
                $attributes['extra'] = $value;

                return $attributes;
            })
            ->afterPersist(function(GenericModel $object, array $attributes) {
                $object->setProp1($attributes['extra']);
            })
            ->create();

        $this->assertSame($value, $object->getProp1());
    }

    /**
     * @return PersistentProxyObjectFactory<GenericModel>
     */
    abstract protected static function factory(): PersistentProxyObjectFactory;

    /**
     * @return PersistentProxyObjectFactory<DocumentWithReadonly|EntityWithReadonly>
     */
    abstract protected function objectWithReadonlyFactory(): PersistentProxyObjectFactory;
}
