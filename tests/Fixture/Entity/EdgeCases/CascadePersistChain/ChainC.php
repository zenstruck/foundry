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

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('cascade_persist_chain_c')]
#[ORM\HasLifecycleCallbacks]
class ChainC extends Base
{
    #[ORM\ManyToOne(targetEntity: ChainB::class, inversedBy: 'cs')]
    private ?ChainB $b = null;

    #[ORM\Column]
    public bool $hasBAtPrePersist = false;

    #[ORM\PrePersist]
    public function capturePrePersistState(): void
    {
        $this->hasBAtPrePersist = null !== $this->b;
    }

    public function getB(): ?ChainB
    {
        return $this->b;
    }

    public function setB(?ChainB $b): void
    {
        $this->b = $b;
    }
}
