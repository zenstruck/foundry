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
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

use function Zenstruck\Foundry\Persistence\unproxy;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit 11.4
 */
#[RequiresPhpunit('11.4')]
abstract class DataProviderWithProxyFactoryInKernelTestCase extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    #[Test]
    #[DataProvider('createOneObjectInDataProvider')]
    public function assert_it_can_create_one_object_in_data_provider(?GenericModel $providedData): void
    {
        if (\FOUNDRY_SKIP_DATA_PROVIDER === $this->dataName()) {
            $this->markTestSkipped();
        }

        static::factory()::assert()->count(1);

        self::assertInstanceOf(Proxy::class, $providedData);
        self::assertNotInstanceOf(Proxy::class, unproxy($providedData)); // asserts two proxies are not nested
        self::assertInstanceOf(GenericModel::class, $providedData);
        self::assertSame('value set in data provider', $providedData->getProp1());
    }

    public static function createOneObjectInDataProvider(): iterable
    {
        if ('1' !== ($_ENV['USE_FOUNDRY_PHPUNIT_EXTENSION'] ?? null)) {
            yield \FOUNDRY_SKIP_DATA_PROVIDER => [null];

            return;
        }

        yield 'createOne()' => [
            static::factory()::createOne(['prop1' => 'value set in data provider']),
        ];

        yield 'create()' => [
            static::factory()->create(['prop1' => 'value set in data provider']),
        ];
    }

    #[DataProvider('createMultipleObjectsInDataProvider')]
    #[Test]
    #[RequiresPhpunit('11.4')]
    public function assert_it_can_create_multiple_objects_in_data_provider(?array $providedData): void
    {
        if (\FOUNDRY_SKIP_DATA_PROVIDER === $this->dataName()) {
            $this->markTestSkipped();
        }

        self::assertIsArray($providedData);
        static::factory()::assert()->count(2);
        self::assertSame('prop 1', $providedData[0]->getProp1());
        self::assertSame('prop 2', $providedData[1]->getProp1());
    }

    public static function createMultipleObjectsInDataProvider(): iterable
    {
        if ('1' !== ($_ENV['USE_FOUNDRY_PHPUNIT_EXTENSION'] ?? null)) {
            yield \FOUNDRY_SKIP_DATA_PROVIDER => [null];

            return;
        }

        yield 'createSequence()' => [
            static::factory()::createSequence([
                ['prop1' => 'prop 1'],
                ['prop1' => 'prop 2'],
            ]),
        ];

        yield 'FactoryCollection::create()' => [
            static::factory()->sequence([
                ['prop1' => 'prop 1'],
                ['prop1' => 'prop 2'],
            ])->create(),
        ];
    }

    /**
     * @return PersistentProxyObjectFactory<GenericModel>
     */
    abstract protected static function factory(): PersistentProxyObjectFactory;
}
