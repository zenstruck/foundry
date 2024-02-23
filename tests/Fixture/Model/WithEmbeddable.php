<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[ORM\MappedSuperclass]
#[MongoDB\MappedSuperclass]
abstract class WithEmbeddable extends Base
{
    #[ORM\Embedded(class: Embeddable::class)]
    #[MongoDB\EmbedOne(targetDocument: Embeddable::class)]
    private Embeddable $embeddable;

    /** @var Collection<int,Embeddable> */
    #[MongoDB\EmbedMany(targetDocument: Embeddable::class)]
    private Collection $embeddables;

    public function __construct(Embeddable $embeddable)
    {
        $this->embeddable = $embeddable;
        $this->embeddables = new ArrayCollection();
    }

    public function getEmbeddable(): Embeddable
    {
        return $this->embeddable;
    }

    public function addEmbeddable(Embeddable $embeddable): void
    {
        $this->embeddables->add($embeddable);
    }

    public function removeEmbeddable(Embeddable $embeddable): void
    {
        $this->embeddables->removeElement($embeddable);
    }

    /**
     * @return Collection<int,Embeddable>
     */
    public function getEmbeddables(): Collection
    {
        return $this->embeddables;
    }
}
