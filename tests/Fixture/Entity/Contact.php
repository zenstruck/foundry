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
#[ORM\Entity]
#[ORM\InheritanceType(value: 'SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type')]
#[ORM\DiscriminatorMap(['simple' => Contact::class, 'specific' => ChildContact::class])]
class Contact extends Base
{
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: true)]
    protected ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'secondaryContacts')]
    protected ?Category $secondaryCategory = null;

    /** @var Collection<int,Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'contacts')]
    protected Collection $tags;

    /** @var Collection<int,Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'secondaryContacts')]
    #[ORM\JoinTable(name: 'category_tag_standard_secondary')]
    protected Collection $secondaryTags;

    #[ORM\OneToOne(targetEntity: Address::class, inversedBy: 'contact')]
    #[ORM\JoinColumn(nullable: false)]
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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
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

    public function addTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
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

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }
}
