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

namespace Zenstruck\Foundry\Tests\Integration\ForceFactoriesTraitUsage;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Stories\ObjectStory;

abstract class KernelTestCaseWithoutFactoriesTraitTestCase extends KernelTestCase
{
    #[Test]
    public function not_using_foundry_should_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function not_using_foundry_should_not_throw_even_when_container_is_used(): void
    {
        self::getContainer()->get('.zenstruck_foundry.configuration');

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    #[IgnoreDeprecations]
    public function using_foundry_without_trait_should_throw(): void
    {
        $this->assertDeprecation();

        Object1Factory::createOne();
    }

    #[Test]
    #[IgnoreDeprecations]
    public function using_foundry_without_trait_should_throw_even_when_kernel_is_booted(): void
    {
        $this->assertDeprecation();

        self::getContainer()->get('.zenstruck_foundry.configuration');

        Object1Factory::createOne();
    }

    #[Test]
    #[RequiresPhpunitExtension(FoundryExtension::class)]
    #[IgnoreDeprecations]
    public function using_a_story_without_factories_trait_should_throw(): void
    {
        $this->assertDeprecation();

        ObjectStory::load();
    }

    /**
     * We need to at least boot and shutdown Foundry to avoid unpredictable behaviors.
     *
     * In user land, Foundry can work without the trait, because it may have been booted in a previous test.
     */
    #[Before]
    public function _beforeHook(): void
    {
        Configuration::boot(static function(): Configuration {
            return static::getContainer()->get('.zenstruck_foundry.configuration'); // @phpstan-ignore return.type
        });
    }

    #[After]
    public static function _shutdownFoundry(): void
    {
        Configuration::shutdown();
    }

    private function assertDeprecation(): void
    {
        $this->expectUserDeprecationMessageMatches('/In order to use Foundry correctly, you must use the trait "Zenstruck\\\\Foundry\\\\Test\\\\Factories/');
    }
}
