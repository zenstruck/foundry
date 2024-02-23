<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RichDomainMandatoryRelationship;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity()]
#[ORM\Table(name: 'rich_domain_mandatory_relationship_inversed_side_entity')]
class InversedSideEntity extends Base
{
    /** @var Collection<int,OwningSideEntity> */
    #[ORM\OneToMany(mappedBy: 'main', targetEntity: OwningSideEntity::class, cascade: ['persist', 'remove'])]
    private Collection $relations;

    public function __construct()
    {
        $this->relations = new ArrayCollection();
    }

    /**
     * @return Collection<int,OwningSideEntity>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }

    public function addRelation(OwningSideEntity $relation): static
    {
        if (!$this->relations->contains($relation)) {
            $this->relations->add($relation);
        }

        return $this;
    }

    public function removeRelation(OwningSideEntity $relation): static
    {
        $this->relations->removeElement($relation);

        return $this;
    }
}
