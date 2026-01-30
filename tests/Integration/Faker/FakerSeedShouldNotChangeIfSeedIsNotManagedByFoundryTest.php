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
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithEnvironmentVariable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\FakerAdapter;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use function Zenstruck\Foundry\faker;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12.0
 */
#[RequiresPhpunit('>=12.0')]
final class FakerSeedShouldNotChangeIfSeedIsNotManagedByFoundryTest extends KernelTestCase
{
    use Factories, ResetDatabase, ResetFakerTestTrait;

    #[Test]
    public function faker_seed_is_null_if_not_forced(): void
    {
        // usually triggers seeding
        faker()->word();

        self::assertNull(FakerAdapter::fakerSeed());
    }

    #[Test]
    #[Depends('faker_seed_is_null_if_not_forced')]
    public function faker_seed_still_null(): void
    {
        $this->faker_seed_is_null_if_not_forced();
    }

    #[Test]
    #[WithEnvironmentVariable('FOUNDRY_FAKER_SEED', '4321')]
    public function faker_seed_can_still_be_forced_by_env_var(): void
    {
        self::assertSame('quia', faker()->word());
        self::assertSame(4321, FakerAdapter::fakerSeed());
    }

    #[Test]
    public function faker_seed_can_still_be_forced_by_env_var_2(): void
    {
        $this->faker_seed_can_still_be_forced_by_env_var();
    }

    protected static function bootKernel(array $options = []): KernelInterface
    {
        return parent::bootKernel(['environment' => 'faker_seed_not_managed']);
    }
}
