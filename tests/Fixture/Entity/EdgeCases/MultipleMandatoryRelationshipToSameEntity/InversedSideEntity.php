<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\MultipleMandatoryRelationshipToSameEntity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
class InversedSideEntity extends Base
{
    /** @var Collection<int,OwningSideEntity> */
    #[ORM\OneToMany(mappedBy: 'main', targetEntity: OwningSideEntity::class, cascade: ['persist', 'remove'])]
    private Collection $mainRelations;

    /** @var Collection<int,OwningSideEntity> */
    #[ORM\OneToMany(mappedBy: 'secondary', targetEntity: OwningSideEntity::class, cascade: ['persist', 'remove'])]
    private Collection $secondaryRelations;

    public function __construct()
    {
        $this->mainRelations = new ArrayCollection();
        $this->secondaryRelations = new ArrayCollection();
    }

    /**
     * @return Collection<int,OwningSideEntity>
     */
    public function getMainRelations(): Collection
    {
        return $this->mainRelations;
    }

    public function addMainRelation(OwningSideEntity $mainRelation): static
    {
        if (!$this->mainRelations->contains($mainRelation)) {
            $this->mainRelations->add($mainRelation);
            $mainRelation->setMain($this);
        }

        return $this;
    }

    public function removeMainRelation(OwningSideEntity $mainRelation): static
    {
        $this->mainRelations->removeElement($mainRelation);

        return $this;
    }

    /**
     * @return Collection<int,OwningSideEntity>
     */
    public function getSecondaryRelations(): Collection
    {
        return $this->secondaryRelations;
    }

    public function addSecondaryRelation(OwningSideEntity $secondaryRelation): static
    {
        if (!$this->secondaryRelations->contains($secondaryRelation)) {
            $this->secondaryRelations->add($secondaryRelation);
            $secondaryRelation->setSecondary($this);
        }

        return $this;
    }

    public function removeSecondaryRelation(OwningSideEntity $secondaryRelation): static
    {
        $this->secondaryRelations->removeElement($secondaryRelation);

        return $this;
    }
}
