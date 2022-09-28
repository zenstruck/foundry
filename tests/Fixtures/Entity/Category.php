<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="categories")
 */
class Category
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
     * @ORM\OneToMany(targetEntity=Post::class, mappedBy="category")
     */
    private $posts;

    /**
     * @ORM\OneToMany(targetEntity=Post::class, mappedBy="secondaryCategory")
     */
    private $secondaryPosts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->secondaryPosts = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getPosts()
    {
        return $this->posts;
    }

    public function addPost(Post $post)
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setCategory($this);
        }
    }

    public function removePost(Post $post)
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            // set the owning side to null (unless already changed)
            if ($post->getCategory() === $this) {
                $post->setCategory(null);
            }
        }
    }

    public function getSecondaryPosts()
    {
        return $this->posts;
    }

    public function addSecondaryPost(Post $secondaryPost)
    {
        if (!$this->secondaryPosts->contains($secondaryPost)) {
            $this->secondaryPosts[] = $secondaryPost;
            $secondaryPost->setCategory($this);
        }
    }

    public function removeSecondaryPost(Post $secondaryPost)
    {
        if ($this->secondaryPosts->contains($secondaryPost)) {
            $this->secondaryPosts->removeElement($secondaryPost);
            // set the owning side to null (unless already changed)
            if ($secondaryPost->getCategory() === $this) {
                $secondaryPost->setCategory(null);
            }
        }
    }
}
