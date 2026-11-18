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

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\StringBackedEnum;

/**
 * The unique constraints on "uniqueByFieldName", "uniqueByColumnName" and "uniqueTogether*" are declared on the child classes.
 */
#[ORM\MappedSuperclass]
#[MongoDB\MappedSuperclass]
abstract class WithUniqueFields extends Base
{
    #[ORM\Column(unique: true)]
    #[MongoDB\Field(type: 'string')]
    #[MongoDB\UniqueIndex]
    public string $uniqueField = '';

    #[ORM\Column(unique: true, nullable: true)]
    #[MongoDB\Field(type: 'string', nullable: true)]
    #[MongoDB\UniqueIndex]
    public ?string $nullableUniqueField = null;

    #[ORM\Column(unique: true, nullable: true)]
    #[MongoDB\Field(type: 'date_immutable', nullable: true)]
    #[MongoDB\UniqueIndex]
    public ?\DateTimeImmutable $nullableUniqueDate = null;

    #[ORM\Column(unique: true)]
    #[MongoDB\Field(type: 'string', enumType: StringBackedEnum::class)]
    #[MongoDB\UniqueIndex]
    public StringBackedEnum $uniqueEnum = StringBackedEnum::SOME_VALUE;

    #[ORM\Column]
    #[MongoDB\Field(type: 'string')]
    public string $uniqueByFieldName = '';

    #[ORM\Column(name: 'unique_by_column_name')]
    #[MongoDB\Field(name: 'unique_by_column_name', type: 'string')]
    public string $uniqueByColumnName = '';

    #[ORM\Column]
    #[MongoDB\Field(type: 'int')]
    public int $uniqueTogether1 = 0;

    #[ORM\Column]
    #[MongoDB\Field(type: 'int')]
    public int $uniqueTogether2 = 0;
}
