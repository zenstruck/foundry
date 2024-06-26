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

#[ORM\MappedSuperclass]
#[ORM\Table(name: 'rich_domain_mandatory_relationship_inversed_side_entity')]
abstract class InversedSideEntity extends Base
{
    /** @var Collection<int,OwningSideEntity> */
    protected Collection $relations;

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
        if ($this->relations->contains($relation)) {
            $this->relations->removeElement($relation);
        }

        return $this;
    }
}
