<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\DerivedIdentity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('edge_case_derived_id_owning_side')]
class OwningSide
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', unique: true)]
    public ?int $id = null;

    #[ORM\OneToOne(targetEntity: InverseSide::class, mappedBy: 'owningSide')]
    public ?InverseSide $inverseSide = null;
}
