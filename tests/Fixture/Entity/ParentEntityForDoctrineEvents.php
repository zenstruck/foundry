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

namespace Zenstruck\Foundry\Tests\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
class ParentEntityForDoctrineEvents extends Base
{
    /** @var Collection<int, ChildEntityForDoctrineEvents> */
    #[ORM\OneToMany(targetEntity: ChildEntityForDoctrineEvents::class, mappedBy: 'parent', cascade: ['persist'])]
    private Collection $children;

    public function __construct(
        #[ORM\Column]
        public string $name,
    ) {
        $this->children = new ArrayCollection();
    }

    public function addChild(ChildEntityForDoctrineEvents $child): void
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->parent = $this;
        }
    }

    public function removeChild(ChildEntityForDoctrineEvents $child): void
    {
        $this->children->removeElement($child);
    }

    /** @return Collection<int, ChildEntityForDoctrineEvents> */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}
