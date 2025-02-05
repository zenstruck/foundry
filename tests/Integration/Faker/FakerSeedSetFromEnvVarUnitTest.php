<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Faker;

use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
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
final class FakerSeedSetFromEnvVarUnitTest extends TestCase
{
    use Factories, FakerTestTrait;

    #[Before(10)]
    public static function __setEnv(): void
    {
        $_ENV['FOUNDRY_FAKER_SEED'] = $_SERVER['FOUNDRY_FAKER_SEED'] = '1234';
    }

    #[Test]
    public function faker_seed_is_set_from_env_var(): void
    {
        self::assertSame(1234, Configuration::fakerSeed());
    }

    #[Test]
    #[Depends('faker_seed_is_set_from_env_var')]
    public function faker_seed_does_not_change(): void
    {
        self::assertSame(1234, Configuration::fakerSeed());
    }

    #[AfterClass(-9)]
    public static function __resetFakerSeedEnv(): void
    {
        unset($_ENV['FOUNDRY_FAKER_SEED'], $_SERVER['FOUNDRY_FAKER_SEED']);
    }
}
