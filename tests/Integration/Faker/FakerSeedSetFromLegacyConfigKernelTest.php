<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Faker;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
final class FakerSeedSetFromLegacyConfigKernelTest extends KernelTestCase
{
    use Factories, ResetDatabase, FakerTestTrait;

    #[Test]
    #[IgnoreDeprecations]
    public function test_faker_seed_by_configuration_is_deprecated(): void
    {
        // let's fake, we're starting from a fresh kernel
        $this->tearDown();
        Configuration::shutdown();
        Configuration::resetFakerSeed();

        self::bootKernel(['environment' => 'faker_seed_legacy_config']);

        self::expectUserDeprecationMessageMatches(
            '/The "faker.seed" configuration is deprecated and will be removed in 3.0/'
        );

        self::assertSame(1234, Configuration::fakerSeed());
    }

    #[Test]
    #[Depends('test_faker_seed_by_configuration_is_deprecated')]
    public function faker_seed_is_already_set(): void
    {
        self::assertSame(1234, Configuration::fakerSeed());
    }
}
