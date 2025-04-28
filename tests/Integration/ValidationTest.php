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

namespace Zenstruck\Foundry\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityForValidation;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\object;

final class ValidationTest extends KernelTestCase
{
    use Factories;

    /** @test */
    #[Test]
    public function it_does_not_validate_object_if_validation_not_enabled(): void
    {
        self::expectNotToPerformAssertions();

        object(EntityForValidation::class);
    }

    /** @test */
    #[Test]
    public function it_throws_if_trying_to_validate_with_validation_not_available(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('Validation is not available.');

        factory(EntityForValidation::class)->withValidation()->create();
    }

    /** @test */
    #[Test]
    public function it_throws_if_validation_enabled_in_foundry_but_disabled_in_symfony(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('Validation support cannot be enabled');

        self::bootKernel(['environment' => 'validation_not_available']);

        object(EntityForValidation::class);
    }

    /** @test */
    #[Test]
    public function it_validates_object_if_validation_forced(): void
    {
        self::expectException(ValidationFailedException::class);
        self::expectExceptionMessageMatches('/This value should not be blank/');

        self::bootKernel(['environment' => 'validation_available']);

        factory(EntityForValidation::class)->withValidation()->create();
    }

    /** @test */
    #[Test]
    public function it_validates_object_if_validation_enabled_globally(): void
    {
        self::expectException(ValidationFailedException::class);

        self::bootKernel(['environment' => 'validation_enabled']);

        object(EntityForValidation::class);
    }

    /** @test */
    #[Test]
    public function validation_can_be_disabled(): void
    {
        self::expectNotToPerformAssertions();

        self::bootKernel(['environment' => 'validation_enabled']);

        factory(EntityForValidation::class)->withoutValidation()->create();
    }

    /** @test */
    #[Test]
    public function it_validates_object_with_validation_groups(): void
    {
        self::expectException(ValidationFailedException::class);
        self::expectExceptionMessageMatches('/This value should be greater than 10/');

        self::bootKernel(['environment' => 'validation_available']);

        factory(EntityForValidation::class)->withValidation('validation_group')->create();
    }

    /** @test */
    #[Test]
    public function it_validates_object_with_validation_groups_when_validation_enabled_globally(): void
    {
        self::expectException(ValidationFailedException::class);
        self::expectExceptionMessageMatches('/This value should be greater than 10/');

        self::bootKernel(['environment' => 'validation_enabled']);

        factory(EntityForValidation::class)->withValidationGroups('validation_group')->create();
    }

    /** @test */
    #[Test]
    public function it_can_erase_validation_groups(): void
    {
        self::expectException(ValidationFailedException::class);
        self::expectExceptionMessageMatches('/This value should not be blank/');

        self::bootKernel(['environment' => 'validation_available']);

        factory(EntityForValidation::class)->withValidation('validation_group')->withValidationGroups(null)->create();
    }
}
