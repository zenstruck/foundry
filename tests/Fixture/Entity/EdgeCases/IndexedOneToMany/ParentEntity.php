<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\IndexedOneToMany;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('index_by_one_to_many_parent')]
class ParentEntity extends Base
{
    /** @var Collection<int, Child> */
    #[ORM\OneToMany(targetEntity: Child::class, mappedBy: 'parent', indexBy: 'language')]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @return Collection<int, Child>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Child $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }
    }

    public function removeItem(Child $item): void
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
        }
    }
}
