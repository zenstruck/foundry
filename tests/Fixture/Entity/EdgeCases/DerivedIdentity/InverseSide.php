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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('edge_case_derived_id_inverse_side')]
class InverseSide
{
    #[ORM\Column(type: Types::STRING)]
    private string $status = 'created';

    public function __construct(
        #[ORM\Id]
        #[ORM\OneToOne(targetEntity: OwningSide::class, inversedBy: 'inverseSide')]
        #[ORM\JoinColumn(name: 'owning_side_id', referencedColumnName: 'id')]
        private OwningSide $owningSide,
    ) {
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getOwningSide(): OwningSide
    {
        return $this->owningSide;
    }

    public function setOwningSide(OwningSide $owningSide): void
    {
        $this->owningSide = $owningSide;
        $owningSide->inverseSide = $this;
    }
}
