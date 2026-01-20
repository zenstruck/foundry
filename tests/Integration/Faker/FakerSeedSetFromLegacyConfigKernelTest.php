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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use function Zenstruck\Foundry\faker;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
final class FakerSeedSetFromLegacyConfigKernelTest extends KernelTestCase
{
    use Factories, ResetFakerTestTrait, ResetDatabase;

    #[Test]
    #[IgnoreDeprecations]
    public function faker_seed_by_configuration_is_deprecated(): void
    {
        self::assertSame('quia', faker()->word());
        self::assertSame(4321, Configuration::fakerSeed());
    }

    #[Test]
    #[Depends('faker_seed_by_configuration_is_deprecated')]
    public function faker_seed_is_already_set(): void
    {
        self::assertSame('quia', faker()->word());
        self::assertSame(4321, Configuration::fakerSeed());
    }

    protected static function bootKernel(array $options = []): KernelInterface
    {
        return parent::bootKernel(['environment' => 'faker_seed_legacy_config']);
    }
}
