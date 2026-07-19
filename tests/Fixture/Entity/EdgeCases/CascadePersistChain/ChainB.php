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
#[ORM\Table('cascade_persist_chain_b')]
#[ORM\HasLifecycleCallbacks]
class ChainB extends Base
{
    #[ORM\ManyToOne(targetEntity: ChainA::class, inversedBy: 'bs')]
    private ?ChainA $a = null;

    /** @var Collection<int, ChainC> */
    #[ORM\OneToMany(targetEntity: ChainC::class, mappedBy: 'b', cascade: ['persist', 'remove'])]
    private Collection $cs;

    #[ORM\Column]
    public int $csCountAtPrePersist = 0;

    #[ORM\Column]
    public bool $hasAAtPrePersist = false;

    public function __construct()
    {
        $this->cs = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function capturePrePersistState(): void
    {
        $this->csCountAtPrePersist = $this->cs->count();
        $this->hasAAtPrePersist = null !== $this->a;
    }

    public function getA(): ?ChainA
    {
        return $this->a;
    }

    public function setA(?ChainA $a): void
    {
        $this->a = $a;
    }

    public function addC(ChainC $c): void
    {
        if (!$this->cs->contains($c)) {
            $this->cs->add($c);
            $c->setB($this);
        }
    }

    public function removeC(ChainC $c): void
    {
        if ($this->cs->removeElement($c) && $c->getB() === $this) {
            $c->setB(null);
        }
    }

    /**
     * @return Collection<int, ChainC>
     */
    public function getCs(): Collection
    {
        return $this->cs;
    }
}
