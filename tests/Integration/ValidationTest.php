<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityForValidation;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\object;

final class ValidationTest extends KernelTestCase
{
    use Factories;

    public function test_it_does_not_validate_object_if_validation_not_enabled(): void
    {
        self::expectNotToPerformAssertions();

        object(EntityForValidation::class);
    }

    public function test_it_throws_if_trying_to_validate_with_validation_not_available(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('Validation is not available.');

        factory(EntityForValidation::class)->withValidation()->create();
    }

    public function test_it_throws_if_validation_enabled_in_foundry_but_disabled_in_symfony(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('Validation support cannot be enabled');

        self::bootKernel(['environment' => 'validation_not_available']);

        object(EntityForValidation::class);
    }

    public function test_it_validates_object_if_validation_forced(): void
    {
        self::expectException(ValidationFailedException::class);

        self::bootKernel(['environment' => 'validation_available']);

        factory(EntityForValidation::class)->withValidation()->create();
    }

    public function test_it_validates_object_if_validation_enabled_globally(): void
    {
        self::expectException(ValidationFailedException::class);

        self::bootKernel(['environment' => 'validation_enabled']);

        object(EntityForValidation::class);
    }

    public function test_validation_can_be_disabled(): void
    {
        self::expectNotToPerformAssertions();

        self::bootKernel(['environment' => 'validation_enabled']);

        factory(EntityForValidation::class)->withoutValidation()->create();
    }
}
