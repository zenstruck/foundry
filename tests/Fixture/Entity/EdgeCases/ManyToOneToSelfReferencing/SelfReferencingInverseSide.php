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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\ManyToOneToSelfReferencing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('many_to_one_to_self_referencing_inverse_side')]
class SelfReferencingInverseSide extends Base
{
    #[ORM\ManyToOne()]
    public ?SelfReferencingInverseSide $inverseSide = null;

    /** @var Collection<int, OwningSide> */
    #[ORM\OneToMany(targetEntity: OwningSide::class, mappedBy: 'inverseSide')]
    public Collection $owningSides;

    public function __construct()
    {
        $this->owningSides = new ArrayCollection();
    }
}
