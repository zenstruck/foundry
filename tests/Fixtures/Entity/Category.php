<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @var mixed|null
     */
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'category')]
    private Collection $posts;

    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'secondaryCategory')]
    private Collection $secondaryPosts;

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

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function updateName(?string $name): void
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
            $post->setCategory($this);
        }
    }

    public function removePost(Post $post): void
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            // set the owning side to null (unless already changed)
            if ($post->getCategory() === $this) {
                $post->setCategory(null);
            }
        }
    }

    public function getSecondaryPosts(): Collection
    {
        return $this->posts;
    }

    public function addSecondaryPost(Post $secondaryPost): void
    {
        if (!$this->secondaryPosts->contains($secondaryPost)) {
            $this->secondaryPosts[] = $secondaryPost;
            $secondaryPost->setSecondaryCategory($this);
        }
    }

    public function removeSecondaryPost(Post $secondaryPost): void
    {
        if ($this->secondaryPosts->contains($secondaryPost)) {
            $this->secondaryPosts->removeElement($secondaryPost);
            // set the owning side to null (unless already changed)
            if ($secondaryPost->getCategory() === $this) {
                $secondaryPost->setSecondaryCategory(null);
            }
        }
    }
}
