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
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Factories;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12.0
 */
#[RequiresPhpunit('>=12.0')]
#[RequiresEnvironmentVariable('FOUNDRY_FAKER_SEED', '1234')]
final class FakerSeedSetFromEnvVarUnitTest extends TestCase
{
    use Factories, FakerTestTrait;

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
}
