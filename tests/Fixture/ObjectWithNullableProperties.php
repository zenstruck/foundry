<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture;

final class ObjectWithNullableProperties
{
    public ?string $nullable = null;
    public ?StringBackedEnum $nullableEnum = null;
    public ?\DateTimeImmutable $nullableDate = null;
    public ?string $nullableWithRequiredSetter = null;
    public ?string $snake_case_nullable_with_required_setter = null;
    public ?string $nullableWithRequiredConstructorParameter;

    public function __construct(public string $required, string $nullableWithRequiredConstructorParameter)
    {
        $this->nullableWithRequiredConstructorParameter = $nullableWithRequiredConstructorParameter;
    }

    public function setNullableWithRequiredSetter(string $nullableWithRequiredSetter): void
    {
        $this->nullableWithRequiredSetter = $nullableWithRequiredSetter;
    }

    public function setSnakeCaseNullableWithRequiredSetter(string $snakeCaseNullableWithRequiredSetter): void
    {
        $this->snake_case_nullable_with_required_setter = $snakeCaseNullableWithRequiredSetter;
    }
}
