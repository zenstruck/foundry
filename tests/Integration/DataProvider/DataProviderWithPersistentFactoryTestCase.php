<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\DataProvider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyGenerator;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\EntityWithReadonly\EntityWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

use function Zenstruck\Foundry\Persistence\assert_persisted;

abstract class DataProviderWithPersistentFactoryTestCase extends KernelTestCase
{
    use ResetDatabase;

    #[Test]
    #[DataProvider('createOneObjectInDataProvider')]
    public function assert_it_can_create_one_object_in_data_provider(?GenericModel $providedData): void
    {
        static::factory()::assert()->count(1);

        self::assertNotNull($providedData);
        self::assertSame('value set in data provider', $providedData->getProp1());

        assert_persisted($providedData);
    }

    #[Test]
    #[DataProvider('createOneObjectInDataProvider')]
    public function assert_it_provided_instance_is_the_same_than_returned_by_repository(?GenericModel $providedData): void
    {
        static::factory()::assert()->count(1);

        self::assertNotNull($providedData);
        self::assertSame(static::factory()::repository()->firstOrFail(), $providedData);
    }

    public static function createOneObjectInDataProvider(): iterable
    {
        yield 'createOne()' => [
            static::factory()::createOne(['prop1' => 'value set in data provider']),
        ];

        yield 'create()' => [
            static::factory()->create(['prop1' => 'value set in data provider']),
        ];
    }

    #[Test]
    #[DataProvider('createOneObjectInDataProvider')]
    public function assert_multiple_tests_can_use_the_same_data_provider(?GenericModel $providedData): void
    {
        $this->assert_it_can_create_one_object_in_data_provider($providedData);
    }

    #[Test]
    #[DataProvider('createOneObjectInDataProvider')]
    public function assert_it_can_use_the_same_object_in_several_data_sets(?GenericModel $providedData): void
    {
        $this->assert_it_can_create_one_object_in_data_provider($providedData);
    }

    public static function canUseSameObjectInSeveralDataSetsProvider(): iterable
    {
        yield 'create object' => [
            $object = static::factory()::createOne(['prop1' => 'value set in data provider']),
        ];

        yield 'use same object' => [
            $object,
        ];
    }

    #[Test]
    #[DataProvider('createMultipleObjectsInDataProvider')]
    public function assert_it_can_create_multiple_objects_in_data_provider(?array $providedData): void
    {
        self::assertIsArray($providedData);
        static::factory()::assert()->count(2);

        self::assertSame('prop 1', $providedData[0]->getProp1());
        self::assertSame('prop 2', $providedData[1]->getProp1());
    }

    public static function createMultipleObjectsInDataProvider(): iterable
    {
        yield 'createSequence()' => [
            static::factory()::createSequence([
                ['prop1' => 'prop 1'],
                ['prop1' => 'prop 2'],
            ]),
        ];

        yield 'sequence()->create()' => [
            static::factory()->sequence([
                ['prop1' => 'prop 1'],
                ['prop1' => 'prop 2'],
            ])->create(),
        ];

        yield 'createMany()' => [
            static::factory()::createMany(2, static fn(int $i) => ['prop1' => "prop {$i}"]),
        ];

        yield 'many()->create()' => [
            static::factory()->many(2)->create(static fn(int $i) => ['prop1' => "prop {$i}"]),
        ];
    }

    #[Test]
    #[DataProvider('dataProviderRetuningArray')]
    public function assert_it_can_use_data_provider_returning_array(?GenericModel $providedData): void
    {
        static::factory()::assert()->count(1);

        self::assertNotNull($providedData);
        self::assertSame('value set in data provider', $providedData->getProp1());
    }

    public static function dataProviderRetuningArray(): array
    {
        return [
            [
                static::factory()::createOne(['prop1' => 'value set in data provider']),
            ],
            [
                static::factory()->create(['prop1' => 'value set in data provider']),
            ],
        ];
    }

    #[Test]
    #[DataProvider('multipleDataProviders1')]
    #[DataProvider('multipleDataProviders2')]
    public function assert_it_can_use_multiple_data_providers(array $providedData, int $expectedCount): void
    {
        static::factory()::assert()->count($expectedCount);

        foreach ($providedData as $providedDatum) {
            self::assertNotNull($providedDatum);
            self::assertSame('value set in data provider', $providedDatum->getProp1());
        }
    }

    public static function multipleDataProviders1(): iterable
    {
        yield [
            static::factory()::createMany(1, ['prop1' => 'value set in data provider']),
            1,
        ];
    }

    public static function multipleDataProviders2(): iterable
    {
        yield [
            static::factory()::createMany(2, ['prop1' => 'value set in data provider']),
            2,
        ];
    }

    #[Test]
    #[DataProvider('useGetterOnPersistedObjectCreatedInDataProvider')]
    public function assert_using_getter_on_persisted_object_created_in_a_data_provider_throws(?\Throwable $e): void
    {
        self::assertInstanceOf(\LogicException::class, $e);
        self::assertStringStartsWith('Cannot access to a persisted object inside a data provider.', $e->getMessage());
    }

    public static function useGetterOnPersistedObjectCreatedInDataProvider(): iterable
    {
        try {
            static::factory()::createOne()->getProp1();
        } catch (\Throwable $e) {
        }

        yield [$e ?? null];
    }

    #[Test]
    #[DataProvider('useGetterOnObjectCreatedInDataProvider')]
    public function assert_it_can_use_getter_on_non_persisted_object_created_in_data_provider(
        string $providedData,
        mixed $expectedData,
    ): void {
        self::assertEquals($expectedData, ProxyGenerator::unwrap($providedData));
    }

    public static function useGetterOnObjectCreatedInDataProvider(): iterable
    {
        yield 'object factory' => [Object1Factory::createOne()->getProp1(), 'router-constructor'];
        yield 'persistent factory' => [static::factory()->withoutPersisting()->create()->getProp1(), 'default1'];
        yield 'persistent factory using many' => [
            static::factory()->withoutPersisting()->many(1)->create()[0]->getProp1(),
            'default1',
        ];
    }

    #[Test]
    #[DataProvider('createOneObjectInDataProviderWithAfterPersistCallback')]
    public function assert_after_persist_callbacks_are_triggered(?GenericModel $providedData): void
    {
        static::factory()::assert()->count(1);

        self::assertSame('after persist callback', $providedData?->getProp1());
    }

    public static function createOneObjectInDataProviderWithAfterPersistCallback(): iterable
    {
        yield [
            static::factory()
                ->afterPersist(fn(GenericModel $object) => $object->setProp1('after persist callback'))
                ->create(),
        ];
    }

    #[Test]
    #[DataProvider('createObjectWithReadonlyProperties')]
    public function assert_it_can_create_objects_with_readonly_properties(DocumentWithReadonly|EntityWithReadonly|null $providedData): void
    {
        static::objectWithReadonlyFactory()::assert()->count(1);

        self::assertSame(1, $providedData?->prop);
    }

    public static function createObjectWithReadonlyProperties(): iterable
    {
        yield [static::objectWithReadonlyFactory()->create()];
    }

    /**
     * @return PersistentObjectFactory<GenericModel>
     */
    abstract protected static function factory(): PersistentObjectFactory;

    /**
     * @return PersistentObjectFactory<DocumentWithReadonly|EntityWithReadonly>
     */
    abstract protected static function objectWithReadonlyFactory(): PersistentObjectFactory;
}
