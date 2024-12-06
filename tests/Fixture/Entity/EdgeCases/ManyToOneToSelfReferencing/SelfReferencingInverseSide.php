<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\ManyToOneToSelfReferencing;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('many_to_one_to_self_referencing_inverse_side')]
class SelfReferencingInverseSide
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\ManyToOne()]
    public ?SelfReferencingInverseSide $inverseSide = null;

    #[ORM\OneToOne(mappedBy: 'inverseSide')]
    public ?OwningSide $owningSide = null;
}
