<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tags")
 */
class Tag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var mixed|null
     * @ORM\Column(type="string", length=255)
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToMany(targetEntity=Post::class, mappedBy="tags")
     */
    private Collection $posts;

    /**
     * @ORM\ManyToMany(targetEntity=Post::class, mappedBy="secondaryTags")
     */
    private Collection $secondaryPosts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->secondaryPosts = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): void
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->addTag($this);
        }
    }

    public function removePost(Post $post): void
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            $post->removeTag($this);
        }
    }

    public function getSecondaryPosts(): Collection
    {
        return $this->secondaryPosts;
    }

    public function addSecondaryPost(Post $secondaryPost): void
    {
        if (!$this->secondaryPosts->contains($secondaryPost)) {
            $this->secondaryPosts[] = $secondaryPost;
            $secondaryPost->addTag($this);
        }
    }

    public function removeSecondaryPost(Post $secondaryPost): void
    {
        if ($this->secondaryPosts->contains($secondaryPost)) {
            $this->secondaryPosts->removeElement($secondaryPost);
            $secondaryPost->removeTag($this);
        }
    }
}
