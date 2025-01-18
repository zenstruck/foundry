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
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Object1;

use function Zenstruck\Foundry\faker;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class DataProviderForServiceFactoryInKernelTestCaseTest extends KernelTestCase
{
    use Factories;

    #[Test]
    #[DataProvider('createObjectFromServiceFactoryInDataProvider')]
    public function it_can_create_one_object_in_data_provider(?Object1 $providedData, string $expected): void
    {
        self::assertFalse(Configuration::instance()->inADataProvider());

        self::assertInstanceOf(Object1::class, $providedData);
        $this->assertSame($expected, $providedData->getProp1());
    }

    public static function createObjectFromServiceFactoryInDataProvider(): iterable
    {
        yield 'service factory' => [
            Object1Factory::createOne(['prop1' => $prop1 = faker()->sentence()]),
            "{$prop1}-constructor",
        ];
    }
}
