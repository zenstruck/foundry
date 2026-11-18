<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('entity_with_unmapped_property')]
class EntityWithUnmappedProperty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(nullable: true)]
    public ?string $prop = null;

    public string $unmapped;

    public function __construct()
    {
        $this->unmapped = 'initial';
    }
}
