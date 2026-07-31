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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\SharedManagedParent;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('shared_managed_parent_root')]
class RootEntity extends Base
{
    /** @var Collection<int, ChildEntity> */
    #[ORM\OneToMany(targetEntity: ChildEntity::class, mappedBy: 'root', cascade: ['persist'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function addItem(ChildEntity $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->root = $this;
        }
    }

    public function removeItem(ChildEntity $item): void
    {
        if ($this->items->removeElement($item) && $item->root === $this) {
            $item->root = null;
        }
    }

    /**
     * @return Collection<int, ChildEntity>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
