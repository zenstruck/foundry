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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Exception\PersistenceDisabled;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Persistence\Exception\NotEnoughObjects;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

use function Zenstruck\Foundry\Persistence\delete;
use function Zenstruck\Foundry\Persistence\disable_persisting;
use function Zenstruck\Foundry\Persistence\enable_persisting;
use function Zenstruck\Foundry\Persistence\flush_after;
use function Zenstruck\Foundry\Persistence\persist;
use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\refresh;
use function Zenstruck\Foundry\Persistence\repository;
use function Zenstruck\Foundry\Persistence\save;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class GenericFactoryTestCase extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     */
    public function can_create_and_update(): void
    {
        $this->factory()::assert()->empty();

        $object = $this->factory()->create();

        $this->assertNotNull($object->id);
        $this->assertSame('default1', $object->getProp1());

        $this->factory()::assert()
            ->count(1)
            ->exists(['prop1' => 'default1'])
            ->notExists(['prop1' => 'invalid'])
        ;

        $this->assertSame($object->id, $this->factory()->first()->id);
        $this->assertSame($object->id, $this->factory()->last()->id);

        $object->setProp1('new value');
        save($object);

        $this->assertSame('new value', $object->getProp1());
        $this->factory()::assert()->exists(['prop1' => 'new value']);
    }

    /**
     * @test
     */
    public function can_disable_auto_persist(): void
    {
        $this->factory()->repository()->assert()->empty();

        $object = $this->factory()->withoutPersisting()->create();

        $this->assertNull($object->id);
        $this->assertSame('default1', $object->getProp1());

        $this->factory()->repository()->assert()->empty();

        save($object);

        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);
    }

    /**
     * @test
     */
    public function can_refresh(): void
    {
        $object = $this->factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);

        self::ensureKernelShutdown();

        // modify and save title "externally"
        $ext = $this->factory()->first();
        $ext->setProp1('external');
        save($ext);

        self::ensureKernelShutdown();

        $refreshed = refresh($object);

        $this->assertSame($refreshed, $object);
        $this->assertSame('external', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'external']);
    }

    /**
     * @test
     */
    public function cannot_refresh_if_there_are_unsaved_changes(): void
    {
        $object = $this->factory()->create();

        // initial data
        $this->assertSame('default1', $object->getProp1());
        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);

        $object->setProp1('new');

        try {
            refresh($object);
        } catch (\RuntimeException) {
            $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);

            return;
        }

        $this->fail('Exception not thrown');
    }

    /**
     * @test
     */
    public function can_delete(): void
    {
        $object = $this->factory()->create();

        $this->factory()->repository()->assert()->exists(['prop1' => 'default1']);

        delete($object);

        $this->factory()->repository()->assert()->empty();
    }

    /**
     * @test
     */
    public function repository_and_create_function(): void
    {
        repository($this->modelClass())->assert()->empty();

        $object = persist($this->modelClass(), ['prop1' => 'value']);

        $this->assertNotNull($object->id);
        $this->assertSame('value', $object->getProp1());

        repository($this->modelClass())->assert()->count(1);
    }

    /**
     * @test
     */
    public function create_many(): void
    {
        $models = $this->factory()->createMany(3, fn(int $i) => ['prop1' => "value{$i}"]);

        $this->factory()::repository()->assert()->count(3);

        $this->assertSame('value1', $models[0]->getProp1());
        $this->assertSame('value2', $models[1]->getProp1());
        $this->assertSame('value3', $models[2]->getProp1());
    }

    /**
     * @test
     */
    public function find(): void
    {
        $object = $this->factory()->create(['prop1' => 'foo']);

        $this->assertSame($object->id, $this->factory()::find($object->id)->id);
        $this->assertSame($object->id, $this->factory()::find(['prop1' => 'foo'])->id);
    }

    /**
     * @test
     */
    public function find_must_return_object(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->factory()::find(1);
    }

    /**
     * @test
     */
    public function find_by(): void
    {
        $this->factory()->create(['prop1' => 'a']);
        $this->factory()->create(['prop1' => 'b']);
        $this->factory()->create(['prop1' => 'b']);

        $this->assertCount(1, $this->factory()::findBy(['prop1' => 'a']));
        $this->assertCount(2, $this->factory()::findBy(['prop1' => 'b']));
    }

    /**
     * @test
     */
    public function find_or_create(): void
    {
        $this->factory()->create(['prop1' => 'a']);

        $this->assertSame('a', $this->factory()::findOrCreate(['prop1' => 'a'])->getProp1());

        $this->factory()::repository()->assert()->count(1);

        $this->assertSame('b', $this->factory()::findOrCreate(['prop1' => 'b'])->getProp1());

        $this->factory()::repository()->assert()->count(2);
    }

    /**
     * @test
     */
    public function random(): void
    {
        $this->factory()->create(['prop1' => 'a']);
        $this->factory()->create(['prop1' => 'b']);
        $this->factory()->create(['prop1' => 'c']);

        $this->assertContains($this->factory()::random()->getProp1(), ['a', 'b', 'c']);
        $this->assertSame('b', $this->factory()::random(['prop1' => 'b'])->getProp1());
    }

    /**
     * @test
     */
    public function random_must_return_an_object(): void
    {
        $this->expectException(NotEnoughObjects::class);

        $this->factory()::random();
    }

    /**
     * @test
     */
    public function random_or_create(): void
    {
        $this->factory()->create(['prop1' => 'a']);

        $this->assertSame('a', $this->factory()::randomOrCreate()->getProp1());
        $this->assertSame('a', $this->factory()::randomOrCreate(['prop1' => 'a'])->getProp1());

        $this->factory()::repository()->assert()->count(1);

        $this->assertSame('b', $this->factory()::randomOrCreate(['prop1' => 'b'])->getProp1());

        $this->factory()::repository()->assert()->count(2);
    }

    /**
     * @test
     */
    public function random_set(): void
    {
        $this->factory()->create(['prop1' => 'a']);
        $this->factory()->create(['prop1' => 'b']);
        $this->factory()->create(['prop1' => 'b']);

        $set = $this->factory()::randomSet(2);

        $this->assertCount(2, $set);
        $this->assertContains($set[0]->getProp1(), ['a', 'b']);
        $this->assertContains($set[1]->getProp1(), ['a', 'b']);

        $set = $this->factory()::randomSet(2, ['prop1' => 'b']);

        $this->assertCount(2, $set);
        $this->assertSame('b', $set[0]->getProp1());
        $this->assertSame('b', $set[1]->getProp1());
    }

    /**
     * @test
     */
    public function random_set_requires_at_least_the_number_available(): void
    {
        $this->factory()::createMany(3);

        $this->expectException(NotEnoughObjects::class);

        $this->factory()::randomSet(4);
    }

    /**
     * @test
     */
    public function random_range(): void
    {
        $this->factory()->create(['prop1' => 'a']);
        $this->factory()->create(['prop1' => 'b']);
        $this->factory()->create(['prop1' => 'b']);
        $this->factory()->create(['prop1' => 'b']);

        $range = $this->factory()::randomRange(0, 3);

        $this->assertGreaterThanOrEqual(0, \count($this));
        $this->assertLessThanOrEqual(3, \count($this));

        foreach ($range as $object) {
            $this->assertContains($object->getProp1(), ['a', 'b']);
        }

        $range = $this->factory()::randomRange(0, 3, ['prop1' => 'b']);

        $this->assertGreaterThanOrEqual(0, \count($this));
        $this->assertLessThanOrEqual(3, \count($this));

        foreach ($range as $object) {
            $this->assertSame('b', $object->getProp1());
        }
    }

    /**
     * @test
     */
    public function random_range_requires_at_least_the_max_available(): void
    {
        $this->factory()::createMany(3);

        $this->expectException(NotEnoughObjects::class);

        $this->factory()::randomRange(1, 5);
    }

    /**
     * @test
     */
    public function factory_count(): void
    {
        $this->factory()::createOne(['prop1' => 'a']);
        $this->factory()::createOne(['prop1' => 'b']);
        $this->factory()::createOne(['prop1' => 'b']);

        $this->assertSame(3, $this->factory()::count());
        $this->assertSame(2, $this->factory()::count(['prop1' => 'b']));
    }

    /**
     * @test
     */
    public function truncate(): void
    {
        $this->factory()::createMany(3);
        $this->factory()::repository()->assert()->count(3);

        $this->factory()::truncate();

        $this->factory()::repository()->assert()->empty();
    }

    /**
     * @test
     */
    public function factory_all(): void
    {
        $this->factory()::createMany(3);

        $this->assertCount(3, $this->factory()::all());
    }

    /**
     * @test
     */
    public function repository_assertions(): void
    {
        $assert = $this->factory()::repository()->assert();

        $assert->empty();
        $assert->empty(['prop1' => 'a']);

        $this->factory()::createOne(['prop1' => 'a']);
        $this->factory()::createOne(['prop1' => 'b']);
        $this->factory()::createOne(['prop1' => 'b']);

        $assert->notEmpty();
        $assert->notEmpty(['prop1' => 'a']);
        $assert->count(3);
        $assert->count(2, ['prop1' => 'b']);
        $assert->countGreaterThan(1);
        $assert->countGreaterThan(1, ['prop1' => 'b']);
        $assert->countGreaterThanOrEqual(3);
        $assert->countGreaterThanOrEqual(2, ['prop1' => 'b']);
        $assert->countLessThan(4);
        $assert->countLessThan(3, ['prop1' => 'b']);
        $assert->countLessThanOrEqual(3);
        $assert->countLessThanOrEqual(2, ['prop1' => 'b']);
        $assert->exists(['prop1' => 'a']);
        $assert->notExists(['prop1' => 'c']);
    }

    /**
     * @test
     */
    public function repository_is_lazy(): void
    {
        $this->factory()::createOne();

        $repository = $this->factory()::repository();

        $object = $repository->random();
        $object->setProp1('new value');
        save($object);

        self::ensureKernelShutdown();

        $repository->assert()->exists(['prop1' => 'new value']);
    }

    /**
     * @test
     */
    public function flush_after(): void
    {
        $this->factory()::repository()->assert()->empty();

        flush_after(function() {
            $object = $this->factory()::createOne();

            // ensure auto-refresh does not break when in flush_after
            $object->getProp1();

            $this->factory()::repository()->assert()->empty();
        });

        $this->factory()::repository()->assert()->count(1);
    }

    /**
     * @test
     */
    public function can_disable_and_enable_persisting_globally(): void
    {
        $this->factory()::repository()->assert()->empty();

        disable_persisting();

        $this->factory()::createOne();
        $this->factory()::new()->create();
        persistent_factory($this->modelClass())->create(['prop1' => 'foo']);
        persist($this->modelClass(), ['prop1' => 'foo']);

        enable_persisting();

        $this->factory()::createOne();
        $this->factory()::repository()->assert()->count(1);
    }

    /**
     * @test
     */
    public function cannot_access_repository_method_when_persist_disabled(): void
    {
        disable_persisting();

        $countErrors = 0;
        try {
            $this->factory()::assert();
        } catch (PersistenceDisabled) {
            ++$countErrors;
        }

        try {
            $this->factory()::repository();
        } catch (PersistenceDisabled) {
            ++$countErrors;
        }

        try {
            $this->factory()::findBy([]);
        } catch (PersistenceDisabled) {
            ++$countErrors;
        }

        self::assertSame(3, $countErrors);
    }

    /**
     * @test
     * @depends cannot_access_repository_method_when_persist_disabled
     */
    public function assert_persist_is_re_enabled_automatically(): void
    {
        $configuration = Configuration::instance();
        self::assertTrue($configuration->isPersistenceAvailable());
        self::assertTrue($configuration->persistence()->isEnabled());

        persist($this->modelClass(), ['prop1' => 'value']);
        $this->factory()::assert()->count(1);
    }

    /**
     * @test
     */
    public function assert_it_ca_create_object_with_dates(): void
    {
        $object = $this->factory()->create(['date' => $date = new \DateTimeImmutable()]);
        self::assertSame($date->format(\DateTimeInterface::ATOM), $object->getDate()?->format(\DateTimeInterface::ATOM));
    }

    /**
     * @return class-string<GenericModel>
     */
    protected function modelClass(): string
    {
        return $this->factory()::class();
    }

    /**
     * @return PersistentObjectFactory<GenericModel>
     */
    abstract protected function factory(): PersistentObjectFactory;
}
