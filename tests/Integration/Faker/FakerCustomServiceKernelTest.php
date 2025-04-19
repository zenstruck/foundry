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

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\ExtendedGenerator;

use function Zenstruck\Foundry\faker;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
final class FakerCustomServiceKernelTest extends KernelTestCase
{
    use Factories, FakerTestTrait, ResetDatabase;

    #[Test]
    public function faker_service_can_be_set(): void
    {
        self::bootKernel(['environment' => 'faker_custom_service']);

        self::assertInstanceOf(ExtendedGenerator::class, faker());
        self::assertSame('custom', faker()->customMethod());

        self::$currentSeed = Configuration::fakerSeed();

        self::assertSame(self::$currentSeed, Configuration::fakerSeed());
    }

    #[Test]
    #[Depends('faker_service_can_be_set')]
    public function faker_seed_does_not_change_between_tests(): void
    {
        self::assertSame(self::$currentSeed, Configuration::fakerSeed());
    }

    #[Test]
    public function faker_service_set_and_seed_is_still_available(): void
    {
        self::bootKernel(['environment' => 'faker_custom_service']);

        self::assertTrue(self::getContainer()->hasParameter('zenstruck_foundry.faker.seed'));
    }
}
