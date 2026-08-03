<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Declares every property itself, privately: casting such an object to an array yields only mangled
 * keys resolving to its own scope, which is the shape that breaks hydration from a snapshot. Sharing
 * a mapped superclass would defeat the purpose, as the inherited scope makes the problem disappear.
 */
#[ORM\Entity]
class EntityWithOnlyPrivateProperties
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
    private string $prop1 = 'default1';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProp1(): string
    {
        return $this->prop1;
    }

    public function setProp1(string $prop1): void
    {
        $this->prop1 = $prop1;
    }
}
