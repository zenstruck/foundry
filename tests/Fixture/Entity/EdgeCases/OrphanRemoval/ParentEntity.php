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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\OrphanRemoval;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('orphan_removal_parent')]
#[ORM\HasLifecycleCallbacks]
class ParentEntity extends Base
{
    /** @var Collection<int, ChildEntity> */
    #[ORM\OneToMany(targetEntity: ChildEntity::class, mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $children;

    #[ORM\Column]
    public int $childrenCount = 0;

    #[ORM\Column]
    public string $name = 'initial';

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function computeChildrenCount(): void
    {
        $this->childrenCount = $this->children->count();
    }

    public function addChild(ChildEntity $child): void
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->parent = $this;
        }
    }

    public function removeChild(ChildEntity $child): void
    {
        if ($this->children->removeElement($child) && $child->parent === $this) {
            $child->parent = null;
        }
    }

    /**
     * @return Collection<int, ChildEntity>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}
