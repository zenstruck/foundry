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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RichDomainMandatoryRelationship;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
#[ORM\Table(name: 'rich_domain_mandatory_relationship_inversed_side')]
class InversedSide extends Base
{
    /** @var Collection<int,OwningSide> */
    #[ORM\OneToMany(targetEntity: OwningSide::class, mappedBy: 'main')]
    protected Collection $relations;

    public function __construct()
    {
        $this->relations = new ArrayCollection();
    }

    /**
     * @return Collection<int,OwningSide>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }

    public function addRelation(OwningSide $relation): static
    {
        if (!$this->relations->contains($relation)) {
            $this->relations->add($relation);
        }

        return $this;
    }

    public function removeRelation(OwningSide $relation): static
    {
        if ($this->relations->contains($relation)) {
            $this->relations->removeElement($relation);
        }

        return $this;
    }
}
