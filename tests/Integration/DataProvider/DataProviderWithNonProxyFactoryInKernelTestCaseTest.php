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
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class DataProviderWithNonProxyFactoryInKernelTestCaseTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    #[Test]
    #[DataProvider('throwsExceptionWhenCreatingObjectInDataProvider')]
    public function it_throws_an_exception_when_trying_to_create_an_object_in_data_provider(?\Throwable $e): void
    {
        self::assertInstanceOf(\LogicException::class, $e);
        self::assertStringStartsWith('Cannot create object in a data provider for non-proxy factories.', $e->getMessage());
    }

    public static function throwsExceptionWhenCreatingObjectInDataProvider(): iterable
    {
        try {
            GenericEntityFactory::createOne();
        } catch (\Throwable $e) {
        }

        yield [$e ?? null];
    }
}
