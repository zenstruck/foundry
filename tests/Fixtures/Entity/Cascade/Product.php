<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_cascade")
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Brand::class, inversedBy="products", cascade={"persist"})
     */
    private $brand;

    /**
     * @ORM\OneToMany(targetEntity=Variant::class, mappedBy="product", cascade={"persist"})
     */
    private $variants;

    /**
     * @ORM\ManyToMany(targetEntity=Category::class, mappedBy="products", cascade={"persist"})
     */
    private $categories;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class, inversedBy="products", cascade={"persist"})
     */
    private $tags;

    /**
     * @ORM\OneToOne(targetEntity=Review::class, mappedBy="product", cascade={"persist"})
     */
    private $review;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getBrand(): Brand
    {
        return $this->brand;
    }

    public function setBrand(Brand $brand): void
    {
        $this->brand = $brand;
    }

    public function getReview(): Review
    {
        return $this->review;
    }

    public function setReview(Review $review): void
    {
        $this->review = $review;
    }

    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(Variant $variant): void
    {
        if (!$this->variants->contains($variant)) {
            $this->variants[] = $variant;
            $variant->setProduct($this);
        }
    }

    public function removeVariant(Variant $variant): void
    {
        if ($this->variants->contains($variant)) {
            $this->variants->removeElement($variant);
        }
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): void
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }
    }

    public function removeCategory(Category $category): void
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
        }
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }
    }

    public function removeTag(Tag $tag): void
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }
    }
}
