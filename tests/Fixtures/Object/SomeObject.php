<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\Tests\Fixtures\Entity\User;

final class SomeObject
{
    public $propertyWithoutType;
    public string $stringMandatory;
    public string|null $stringNullable;
    public string $stringWithDefault = '';
    public int $intMandatory;
    public float $floatMandatory;
    public array $arrayMandatory;
    public \DateTime $dateTimeMandatory;
    public \DateTimeImmutable $dateTimeImmutableMandatory;
    public SomeOtherObject $someOtherObjectMandatory;
    public SomeOtherObject|SomeObject $someMandatoryPropertyWithUnionType;
    public User $user;
}
