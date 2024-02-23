<?php

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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[ORM\MappedSuperclass]
abstract class Contact extends Base
{
    protected Category|null $category = null;

    protected Category|null $secondaryCategory = null;

    /** @var Collection<int,Tag> */
    protected Collection $tags;

    /** @var Collection<int,Tag> */
    protected Collection $secondaryTags;

    protected Address $address;

    #[ORM\Column(length: 255)]
    private string $name;

    public function __construct(string $name, Address $address)
    {
        $this->name = $name;
        $this->address = $address;
        $this->tags = new ArrayCollection();
        $this->secondaryTags = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSecondaryCategory(): ?Category
    {
        return $this->secondaryCategory;
    }

    public function setSecondaryCategory(?Category $secondaryCategory): void
    {
        $this->secondaryCategory = $secondaryCategory;
    }

    /**
     * @return Collection<int,Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * @return Collection<int,Tag>
     */
    public function getSecondaryTags(): Collection
    {
        return $this->secondaryTags;
    }

    public function addSecondaryTag(Tag $secondaryTag): void
    {
        if (!$this->secondaryTags->contains($secondaryTag)) {
            $this->secondaryTags[] = $secondaryTag;
        }
    }

    public function removeSecondaryTag(Tag $tag): void
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): static
    {
        $this->address = $address;

        return $this;
    }
}
