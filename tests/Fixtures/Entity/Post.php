<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Zenstruck\Foundry\Tests\Fixtures\Repository\PostRepository")
 * @ORM\Table(name="posts")
 */
class Post
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
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shortDescription;

    /**
     * @ORM\Column(type="integer")
     */
    private $viewCount = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $publishedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="posts")
     * @ORM\JoinColumn
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="secondaryPosts")
     * @ORM\JoinColumn(name="secondary_category_id")
     */
    private $secondaryCategory;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class, inversedBy="posts")
     */
    private $tags;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class, inversedBy="secondaryPosts")
     * @ORM\JoinTable(name="post_tag_secondary")
     */
    private $secondaryTags;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="post")
     */
    private $comments;

    /**
     * @ORM\OneToOne(targetEntity=Post::class, inversedBy="mostRelevantRelatedToPost")
     */
    private $mostRelevantRelatedPost;

    /**
     * @ORM\OneToOne(targetEntity=Post::class, mappedBy="mostRelevantRelatedPost")
     */
    private $mostRelevantRelatedToPost;

    /**
     * @ORM\OneToOne(targetEntity=Post::class, inversedBy="lessRelevantRelatedToPost")
     */
    private $lessRelevantRelatedPost;

    /**
     * @ORM\OneToOne(targetEntity=Post::class, mappedBy="lessRelevantRelatedPost")
     */
    private $lessRelevantRelatedToPost;

    public function __construct(string $title, string $body, ?string $shortDescription = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->shortDescription = $shortDescription;
        $this->createdAt = new \DateTime('now');
        $this->tags = new ArrayCollection();
        $this->secondaryTags = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function increaseViewCount(int $amount = 1): void
    {
        $this->viewCount += $amount;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category)
    {
        $this->category = $category;
    }

    public function getSecondaryCategory(): ?Category
    {
        return $this->secondaryCategory;
    }

    public function setSecondaryCategory(?Category $secondaryCategory)
    {
        $this->secondaryCategory = $secondaryCategory;
    }

    public function isPublished(): bool
    {
        return null !== $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $timestamp)
    {
        $this->publishedAt = $timestamp;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function addTag(Tag $tag)
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }
    }

    public function removeTag(Tag $tag)
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }
    }

    public function getSecondaryTags()
    {
        return $this->secondaryTags;
    }

    public function addSecondaryTag(Tag $secondaryTag)
    {
        if (!$this->secondaryTags->contains($secondaryTag)) {
            $this->secondaryTags[] = $secondaryTag;
        }
    }

    public function removeSecondaryTag(Tag $tag)
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setPost($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }

        return $this;
    }

    public function getMostRelevantRelatedPost(): ?self
    {
        return $this->mostRelevantRelatedPost;
    }

    public function setMostRelevantRelatedPost(?self $mostRelevantRelatedPost)
    {
        $this->mostRelevantRelatedPost = $mostRelevantRelatedPost;
    }

    public function getMostRelevantRelatedToPost(): ?self
    {
        return $this->mostRelevantRelatedToPost;
    }

    public function setMostRelevantRelatedToPost(?self $mostRelevantRelatedToPost)
    {
        $this->mostRelevantRelatedToPost = $mostRelevantRelatedToPost;
    }

    public function getLessRelevantRelatedPost(): ?self
    {
        return $this->lessRelevantRelatedPost;
    }

    public function setLessRelevantRelatedPost(?self $lessRelevantRelatedPost)
    {
        $this->lessRelevantRelatedPost = $lessRelevantRelatedPost;
    }

    public function getLessRelevantRelatedToPost(): ?self
    {
        return $this->lessRelevantRelatedToPost;
    }

    public function setLessRelevantRelatedToPost(?self $lessRelevantRelatedToPost)
    {
        $this->lessRelevantRelatedToPost = $lessRelevantRelatedToPost;
    }
}
