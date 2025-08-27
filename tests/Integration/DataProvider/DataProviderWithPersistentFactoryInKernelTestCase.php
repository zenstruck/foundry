<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

use function Zenstruck\Foundry\Persistence\unproxy;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[IgnoreDeprecations]
abstract class DataProviderWithPersistentFactoryInKernelTestCase extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    #[Test]
    #[DataProvider('createOneObjectInDataProvider')]
    public function assert_it_can_create_one_object_in_data_provider(?GenericModel $providedData): void
    {
        static::proxyFactory()::assert()->count(1);

        self::assertInstanceOf(Proxy::class, $providedData);
        self::assertNotInstanceOf(Proxy::class, unproxy($providedData)); // asserts two proxies are not nested
        self::assertSame('value set in data provider', $providedData->getProp1());
    }

    public static function createOneObjectInDataProvider(): iterable
    {
        yield 'createOne()' => [
            static::proxyFactory()::createOne(['prop1' => 'value set in data provider']),
        ];

        yield 'create()' => [
            static::proxyFactory()->create(['prop1' => 'value set in data provider']),
        ];
    }

    #[Test]
    #[DataProvider('createMultipleObjectsInDataProvider')]
    public function assert_it_can_create_multiple_objects_in_data_provider(?array $providedData): void
    {
        self::assertIsArray($providedData);
        static::proxyFactory()::assert()->count(2);
        self::assertSame('prop 1', $providedData[0]->getProp1());
        self::assertSame('prop 2', $providedData[1]->getProp1());
    }

    public static function createMultipleObjectsInDataProvider(): iterable
    {
        yield 'createSequence()' => [
            static::proxyFactory()::createSequence([
                ['prop1' => 'prop 1'],
                ['prop1' => 'prop 2'],
            ]),
        ];

        yield 'FactoryCollection::create()' => [
            static::proxyFactory()->sequence([
                ['prop1' => 'prop 1'],
                ['prop1' => 'prop 2'],
            ])->create(),
        ];
    }

    #[Test]
    #[DataProvider('useGetterOnProxyObjectCreatedInDataProvider')]
    public function assert_using_getter_proxy_object_created_in_a_data_provider_throws(?\Throwable $e): void
    {
        self::assertInstanceOf(\LogicException::class, $e);
        self::assertStringStartsWith('Cannot access to a persisted object from a data provider.', $e->getMessage());
    }

    public static function useGetterOnProxyObjectCreatedInDataProvider(): iterable
    {
        try {
            static::proxyFactory()::createOne()->getProp1();
        } catch (\Throwable $e) {
        }

        yield [$e ?? null];
    }

    #[Test]
    #[DataProvider('throwsExceptionWhenCreatingObjectInDataProvider')]
    public function it_throws_when_creating_persisted_object_with_non_proxy_factory_in_data_provider(?\Throwable $e
    ): void {
        self::assertInstanceOf(\LogicException::class, $e);
        self::assertStringStartsWith(
            'Cannot create object in a data provider for non-proxy factories.',
            $e->getMessage()
        );
    }

    public static function throwsExceptionWhenCreatingObjectInDataProvider(): iterable
    {
        try {
            static::factory()::createOne();
        } catch (\Throwable $e) {
        }

        yield [$e ?? null];
    }

    #[Test]
    #[DataProvider('useGetterOnObjectCreatedInDataProvider')]
    public function assert_it_can_use_getter_on_non_persisted_object_created_in_data_provider(
        string $providedData,
        mixed $expectedData
    ): void {
        self::assertEquals($expectedData, unproxy($providedData));
    }

    public static function useGetterOnObjectCreatedInDataProvider(): iterable
    {
        yield 'object factory' => [Object1Factory::createOne()->getProp1(), 'router-constructor'];
        yield 'persistent factory' => [static::factory()::new()->withoutPersisting()->create()->getProp1(), 'default1'];
        yield 'persistent factory using many' => [
            static::factory()::new()->withoutPersisting()->many(1)->create()[0]->getProp1(),
            'default1'
        ];
        yield 'proxy factory' => [static::proxyFactory()::new()->withoutPersisting()->create()->getProp1(), 'default1'];
        yield 'proxy factory using many' => [
            static::proxyFactory()::new()->withoutPersisting()->many(1)->create()[0]->getProp1(),
            'default1'
        ];
    }

    /**
     * @return PersistentProxyObjectFactory<GenericModel>
     */
    abstract protected static function proxyFactory(): PersistentProxyObjectFactory;

    /**
     * @return PersistentObjectFactory<GenericModel>
     */
    abstract protected static function factory(): PersistentObjectFactory;
}
