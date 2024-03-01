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

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

final class SomeObject
{
    public $propertyWithoutType;
    public string $stringMandatory;
    public ?string $stringNullable;
    public string $stringWithDefault = '';
    public int $intMandatory;
    public float $floatMandatory;
    public array $arrayMandatory;
    public \DateTime $dateTimeMandatory;
    public \DateTimeImmutable $dateTimeImmutableMandatory;
    public SomeOtherObject $someOtherObjectMandatory;
    public SomeOtherObject|SomeObject $someMandatoryPropertyWithUnionType;
}
