<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InversedOneToOneWithNonNullableOwning;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
class OwningSide
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'owningSide')]
    public InverseSide|null $inverseSide = null;
}
