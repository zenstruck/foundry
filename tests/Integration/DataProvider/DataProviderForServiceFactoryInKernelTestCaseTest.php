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
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Object1;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit 11.4
 */
#[RequiresPhpunit('11.4')]
final class DataProviderForServiceFactoryInKernelTestCaseTest extends KernelTestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    #[DataProvider('createObjectFromServiceFactoryInDataProvider')]
    public function it_can_create_one_object_in_data_provider(?Object1 $providedData): void
    {
        if (\FOUNDRY_SKIP_DATA_PROVIDER === $this->dataName()) {
            $this->markTestSkipped();
        }

        self::assertInstanceOf(Object1::class, $providedData);
        $this->assertSame('router-constructor', $providedData->getProp1());
    }

    public static function createObjectFromServiceFactoryInDataProvider(): iterable
    {
        if ('1' !== ($_ENV['USE_FOUNDRY_PHPUNIT_EXTENSION'] ?? null)) {
            yield \FOUNDRY_SKIP_DATA_PROVIDER => [null];

            return;
        }

        yield 'service factory' => [
            Object1Factory::createOne(),
        ];
    }
}
