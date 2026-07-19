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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\CascadePersistChain;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('cascade_persist_chain_a')]
#[ORM\HasLifecycleCallbacks]
class ChainA extends Base
{
    /** @var Collection<int, ChainB> */
    #[ORM\OneToMany(targetEntity: ChainB::class, mappedBy: 'a', cascade: ['persist', 'remove'])]
    private Collection $bs;

    #[ORM\Column]
    public int $bsCountAtPrePersist = 0;

    public function __construct()
    {
        $this->bs = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function computeBsCount(): void
    {
        $this->bsCountAtPrePersist = $this->bs->count();
    }

    public function addB(ChainB $b): void
    {
        if (!$this->bs->contains($b)) {
            $this->bs->add($b);
            $b->setA($this);
        }
    }

    public function removeB(ChainB $b): void
    {
        if ($this->bs->removeElement($b) && $b->getA() === $this) {
            $b->setA(null);
        }
    }

    /**
     * @return Collection<int, ChainB>
     */
    public function getBs(): Collection
    {
        return $this->bs;
    }
}
