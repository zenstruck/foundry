<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Faker;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Factories;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
final class FakerSeedAutomaticallySetUnitTest extends TestCase
{
    use Factories, FakerTestTrait;

    #[Test]
    public function faker_seed_does_not_change(): void
    {
        self::$currentSeed = Configuration::fakerSeed();

        self::assertSame(self::$currentSeed, Configuration::fakerSeed());
    }

    #[Test]
    #[Depends('faker_seed_does_not_change')]
    public function faker_seed_does_not_change_between_tests(): void
    {
        self::assertSame(self::$currentSeed, Configuration::fakerSeed());
    }
}
