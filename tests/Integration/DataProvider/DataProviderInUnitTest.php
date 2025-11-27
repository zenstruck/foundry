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
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Persistence\ProxyGenerator;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericProxyEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object2Factory;
use Zenstruck\Foundry\Tests\Fixture\Object1;
use Zenstruck\Foundry\Tests\Fixture\Object2;

use Zenstruck\Foundry\Tests\Fixture\TestKernel;
use function Zenstruck\Foundry\faker;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class DataProviderInUnitTest extends TestCase
{
    use Factories;

    #[Test]
    #[DataProvider('createObjectWithObjectFactoryInDataProvider')]
    public function assert_it_can_create_object_with_object_factory_in_data_provider(mixed $providedData, mixed $expectedData): void
    {
        self::assertEquals($expectedData, $providedData);
    }

    public static function createObjectWithObjectFactoryInDataProvider(): iterable
    {
        yield 'object factory' => [Object2Factory::createOne(['object' => new Object1('prop1')]), new Object2(new Object1('prop1'))];
        yield 'service factory can be used if dependency is optional' => [Object1Factory::createOne(), new Object1('value1')];
    }

    #[Test]
    #[DataProvider('createObjectWithPersistentObjectFactoryInDataProvider')]
    #[IgnoreDeprecations]
    public function assert_it_can_create_object_with_persistent_factory_in_data_provider(mixed $providedData, mixed $expectedData): void
    {
        self::assertEquals($expectedData, ProxyGenerator::unwrap($providedData));
    }

    public static function createObjectWithPersistentObjectFactoryInDataProvider(): iterable
    {
        yield 'persistent factory' => [GenericEntityFactory::createOne(), new GenericEntity('default1')];

        if (TestKernel::canUseLegacyProxy()) {
            yield 'proxy persistent factory' => [GenericProxyEntityFactory::createOne(), new GenericEntity('default1')];
        }
    }

    #[Test]
    #[DataProvider('useGetterOnObjectCreatedInDataProvider')]
    #[IgnoreDeprecations]
    public function assert_it_can_use_getter_on_object_created_in_data_provider(string $providedData, mixed $expectedData): void
    {
        self::assertEquals($expectedData, ProxyGenerator::unwrap($providedData));
    }

    public static function useGetterOnObjectCreatedInDataProvider(): iterable
    {
        yield 'object factory' => [Object1Factory::createOne()->getProp1(), 'value1-constructor'];
        yield 'persistent factory' => [GenericEntityFactory::createOne()->getProp1(), 'default1'];

        if (TestKernel::canUseLegacyProxy()) {
            yield 'proxy persistent factory' => [GenericProxyEntityFactory::createOne()->getProp1(), 'default1'];
            yield 'proxy persistent factory using many' => [GenericProxyEntityFactory::createMany(1)[0]->getProp1(), 'default1'];
        }
    }

    #[Test]
    #[DataProvider('createObjectUsingFakerInDataProvider')]
    public function assert_it_can_create_use_faker_in_data_provider(mixed $providedData, string $expected): void
    {
        self::assertSame($expected, $providedData->getProp1());
    }

    public static function createObjectUsingFakerInDataProvider(): iterable
    {
        yield 'object factory' => [Object1Factory::createOne(['prop1' => $prop1 = faker()->sentence()]), "{$prop1}-constructor"];
        yield 'persistent factory' => [GenericEntityFactory::createOne(['prop1' => $prop1 = faker()->sentence()]), $prop1];
    }
}
