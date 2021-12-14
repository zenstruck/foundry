<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="foos")
 */
class Foo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Category::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $oneToOne;

    /**
     * @ORM\OneToMany(targetEntity=Category::class, mappedBy="foo")
     */
    private $oneToMany;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $manyToOne;

    /**
     * @ORM\ManyToMany(targetEntity=Category::class)
     */
    private $manyToMany;

    public function __construct()
    {
        $this->oneToMany = new ArrayCollection();
        $this->manyToMany = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOneToOne(): ?Category
    {
        return $this->oneToOne;
    }

    public function setOneToOne(Category $oneToOne): self
    {
        $this->oneToOne = $oneToOne;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getOneToMany(): Collection
    {
        return $this->oneToMany;
    }

    public function addOneToMany(Category $oneToMany): self
    {
        if (!$this->oneToMany->contains($oneToMany)) {
            $this->oneToMany[] = $oneToMany;
            $oneToMany->setFoo($this);
        }

        return $this;
    }

    public function removeOneToMany(Category $oneToMany): self
    {
        if ($this->oneToMany->removeElement($oneToMany)) {
            // set the owning side to null (unless already changed)
            if ($oneToMany->getFoo() === $this) {
                $oneToMany->setFoo(null);
            }
        }

        return $this;
    }

    public function getManyToOne(): ?Category
    {
        return $this->manyToOne;
    }

    public function setManyToOne(?Category $manyToOne): self
    {
        $this->manyToOne = $manyToOne;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getManyToMany(): Collection
    {
        return $this->manyToMany;
    }

    public function addManyToMany(Category $manyToMany): self
    {
        if (!$this->manyToMany->contains($manyToMany)) {
            $this->manyToMany[] = $manyToMany;
        }

        return $this;
    }

    public function removeManyToMany(Category $manyToMany): self
    {
        $this->manyToMany->removeElement($manyToMany);

        return $this;
    }
}
