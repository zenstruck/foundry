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
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\FakerAdapter;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\faker;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12.0.0
 */
#[RequiresPhpunit('>=12.0.0')]
final class FakerSeedSetFromEnvVarUnitTest extends TestCase
{
    use Factories;

    #[Test]
    public function faker_seed_is_set_from_env_var(): void
    {
        self::assertSame('1234', $_SERVER['FOUNDRY_FAKER_SEED'], 'Default seed should be 1234');
        // without Foundry's PHPUnit extension, no test id is available: the run's seed is used as-is
        self::assertSame(FoundryExtension::isEnabled() ? 'nemo' : 'architecto', faker()->word());
        self::assertSame(1234, FakerAdapter::fakerSeed());
    }

    #[Test]
    #[Depends('faker_seed_is_set_from_env_var')]
    public function faker_seed_does_not_change(): void
    {
        self::assertSame('1234', $_SERVER['FOUNDRY_FAKER_SEED'], 'Default seed should be 1234');
        // the run's seed is unchanged, but each test derives its own faker sequence from it
        self::assertSame(FoundryExtension::isEnabled() ? 'quaerat' : 'architecto', faker()->word());
        self::assertSame(1234, FakerAdapter::fakerSeed());
    }
}
