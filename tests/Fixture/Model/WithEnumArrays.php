<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\IntBackedEnum;
use Zenstruck\Foundry\Tests\Fixture\StringBackedEnum;

#[ORM\MappedSuperclass]
#[MongoDB\MappedSuperclass]
abstract class WithEnumArrays extends Base
{
    /** @var list<StringBackedEnum> */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, enumType: StringBackedEnum::class)]
    #[MongoDB\Field(type: 'collection', enumType: StringBackedEnum::class)]
    public array $stringEnums = [];

    /** @var array<IntBackedEnum> */
    #[ORM\Column(type: Types::JSON, enumType: IntBackedEnum::class)]
    #[MongoDB\Field(type: 'hash', enumType: IntBackedEnum::class)]
    public array $intEnums = []; // @phpstan-ignore doctrine.enumType (phpstan-doctrine does not support enumType on json columns)
}
