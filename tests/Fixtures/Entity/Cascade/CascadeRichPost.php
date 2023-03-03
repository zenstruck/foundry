<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class CascadeRichPost
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $title;

    /**
     * @ORM\OneToMany(targetEntity=CascadeRichComment::class, mappedBy="post", orphanRemoval=true, cascade={"persist"})
     */
    private Collection $comments;

    public function __construct(string $title)
    {
        $this->title = $title;
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return Collection<CascadeRichComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(CascadeRichComment $comment): void
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }
    }

    public function removeComment(CascadeRichComment $comment): void
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
        }
    }
}
