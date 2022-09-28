<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity=Post::class, mappedBy="tags")
     */
    private $posts;

    /**
     * @ORM\ManyToMany(targetEntity=Post::class, mappedBy="secondaryTags")
     */
    private $secondaryPosts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->secondaryPosts = new ArrayCollection();
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
            $post->addTag($this);
        }
    }

    public function removePost(Post $post)
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            $post->removeTag($this);
        }
    }

    public function getSecondaryPosts()
    {
        return $this->secondaryPosts;
    }

    public function addSecondaryPost(Post $secondaryPost)
    {
        if (!$this->secondaryPosts->contains($secondaryPost)) {
            $this->secondaryPosts[] = $secondaryPost;
            $secondaryPost->addTag($this);
        }
    }

    public function removeSecondaryPost(Post $secondaryPost)
    {
        if ($this->secondaryPosts->contains($secondaryPost)) {
            $this->secondaryPosts->removeElement($secondaryPost);
            $secondaryPost->removeTag($this);
        }
    }
}
