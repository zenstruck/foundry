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

/**
 * Used for ORM/Mongo tests.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[ORM\MappedSuperclass]
#[MongoDB\MappedSuperclass]
abstract class GenericModel
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[MongoDB\Id(type: 'int', strategy: 'INCREMENT')]
    public ?int $id = null;

    #[ORM\Column]
    #[MongoDB\Field(type: 'string')]
    private string $prop1;

    #[ORM\Column]
    #[MongoDB\Field(type: 'int')]
    private int $propInteger = 0;

    #[ORM\Column(nullable: true)]
    #[MongoDB\Field(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $date = null;

    public function __construct(string $prop1)
    {
        $this->prop1 = $prop1;
    }

    public function getProp1(): string
    {
        return $this->prop1;
    }

    public function setProp1(string $prop1): void
    {
        $this->prop1 = $prop1;
    }

    public function getPropInteger(): int
    {
        return $this->propInteger;
    }

    public function setPropInteger(int $propInteger): void
    {
        $this->propInteger = $propInteger;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }
}
