<?php

declare(strict_types=1);

namespace Integration;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;

final class BundleTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    #[Test]
    #[RequiresPhpunit('>=11.0')]
    #[IgnoreDeprecations]
    public function test_faker_seed_by_configuration_is_deprecated(): void
    {
        self::expectUserDeprecationMessageMatches(
            '/The "faker.seed" configuration is deprecated and will be removed in 3.0/'
        );

        self::bootKernel(['environment' => 'legacy_faker_seed']);

        self::assertSame('Baileyshire', AddressFactory::createOne()->getCity());
    }
}
