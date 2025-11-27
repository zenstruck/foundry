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
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12
 */
#[RequiresPhpunit('>=12')]
#[RequiresPhp('>=8.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '1')]
abstract class DataProviderWithPersistentFactoryAndPHP84InKernelTestCase extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    #[Test]
    #[DataProvider('createOneObjectInDataProvider')]
    public function assert_it_can_create_one_object_in_data_provider(?GenericModel $providedData): void
    {
        static::factory()::assert()->count(1);

        self::assertNotNull($providedData);
        self::assertFalse((new \ReflectionClass($providedData))->isUninitializedLazyObject($providedData));
        self::assertSame('value set in data provider', $providedData->getProp1());
    }

    public static function createOneObjectInDataProvider(): iterable
    {
        yield 'createOne()' => [
            static::factory()::createOne(['prop1' => 'value set in data provider']),
        ];

        yield 'create()' => [
            static::factory()::new()->create(['prop1' => 'value set in data provider']),
        ];
    }

    #[Test]
    #[DataProvider('createMultipleObjectsInDataProvider')]
    public function assert_it_can_create_multiple_objects_in_data_provider(?array $providedData): void
    {
        self::assertIsArray($providedData);
        static::factory()::assert()->count(2);

        foreach ($providedData as $providedDatum) {
            self::assertFalse((new \ReflectionClass($providedDatum))->isUninitializedLazyObject($providedDatum));
        }

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

        yield 'FactoryCollection::create()' => [
            static::factory()::new()->sequence([
                ['prop1' => 'prop 1'],
                ['prop1' => 'prop 2'],
            ])->create(),
        ];
    }

    /**
     * @return PersistentObjectFactory<GenericModel>
     */
    abstract protected static function factory(): PersistentObjectFactory;
}
