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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InversedOneToOneWithOneToMany;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('inversed_one_to_one_with_one_to_many_owning_side')]
class OwningSide extends Base
{
    #[ORM\OneToOne(inversedBy: 'owningSide')]
    public ?InverseSide $inverseSide = null;

    /** @var Collection<int, Item> */
    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'owningSide')]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->owningSide = $this;
        }
    }

    public function removeItem(Item $item): void
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
            $item->owningSide = null;
        }
    }
}
