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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\AccessorDirectionality;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('accessor_directionality_category')]
class CategoryEntity extends Base
{
    /** @var Collection<int, ItemEntity> */
    #[ORM\OneToMany(targetEntity: ItemEntity::class, mappedBy: 'category', cascade: ['persist', 'remove'])]
    private Collection $items;

    public int $adderCalls = 0;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function addItem(ItemEntity $item): void
    {
        ++$this->adderCalls;

        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCategory($this);
        }
    }

    public function removeItem(ItemEntity $item): void
    {
        if ($this->items->removeElement($item) && $item->getCategory() === $this) {
            $item->setCategory(null);
        }
    }

    /**
     * @return Collection<int, ItemEntity>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
