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

namespace Zenstruck\Foundry\Tests\Integration\Faker;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\FakerAdapter;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\WithUniqueColumn;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\WithUniqueColumn\WithUniqueColumnFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\faker;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12.0
 */
#[RequiresPhpunit('>=12.0')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[RequiresPhp('^8.4')]
final class FakerSeedDoNotConflictWithDataProviderTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    #[Test]
    #[DataProvider('provideObject')]
    public function no_conflict_with_data_providers(WithUniqueColumn $withUniqueColumnFromDataProvider): void
    {
        self::assertSame(1234, FakerAdapter::fakerSeed());
        self::assertSame('architecto', $withUniqueColumnFromDataProvider->getUniqueCol());
        self::assertSame('eius', WithUniqueColumnFactory::createOne()->getUniqueCol());
    }

    public static function provideObject(): iterable
    {
        yield [WithUniqueColumnFactory::createOne()];
        yield [WithUniqueColumnFactory::createOne()];
    }

    #[Test]
    #[DataProvider('provideObjectUsingFakreInDataProvider')]
    public function no_conflict_with_data_providers_using_faker(WithUniqueColumn $withUniqueColumnFromDataProvider, string $expected): void
    {
        self::assertSame(1234, FakerAdapter::fakerSeed());
        self::assertSame($expected, $withUniqueColumnFromDataProvider->getUniqueCol());
        self::assertSame('eius', WithUniqueColumnFactory::createOne()->getUniqueCol());
    }

    public static function provideObjectUsingFakreInDataProvider(): iterable
    {
        yield [WithUniqueColumnFactory::createOne(['uniqueCol' => faker()->word()]), 'dolorum'];
        yield [WithUniqueColumnFactory::createOne(['uniqueCol' => faker()->word()]), 'soluta'];

        self::assertSame(1234, FakerAdapter::fakerSeed(), 'Faker seed should have been set even in data provider');
    }
}
