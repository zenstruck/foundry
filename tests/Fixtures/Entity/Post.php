<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixtures\Repository\PostRepository;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: "posts")]
#[ORM\InheritanceType(value: "SINGLE_TABLE")]
#[ORM\DiscriminatorColumn(name: "type")]
#[ORM\DiscriminatorMap(["simple" => Post::class, "specific" => SpecificPost::class])]
class Post implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $body;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $shortDescription;

    #[ORM\Column(type: "integer")]
    private int $viewCount = 0;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $publishedAt = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: "posts")]
    #[ORM\JoinColumn]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: "secondaryPosts")]
    #[ORM\JoinColumn(name: "secondary_category_id")]
    private ?Category $secondaryCategory = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: "posts")]
    private Collection $tags;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: "secondaryPosts")]
    #[ORM\JoinTable(name: "post_tag_secondary")]
    private Collection $secondaryTags;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: "post")]
    private Collection $comments;

    #[ORM\OneToOne(targetEntity: Post::class, inversedBy: "mostRelevantRelatedToPost")]
    private ?Post $mostRelevantRelatedPost = null;

    #[ORM\OneToOne(targetEntity: Post::class, mappedBy: "mostRelevantRelatedPost")]
    private ?Post $mostRelevantRelatedToPost = null;

    #[ORM\OneToOne(targetEntity: Post::class, inversedBy: "lessRelevantRelatedToPost")]
    private ?Post $lessRelevantRelatedPost = null;

    #[ORM\OneToOne(targetEntity: Post::class, mappedBy: "lessRelevantRelatedPost")]
    private ?Post $lessRelevantRelatedToPost = null;

    #[ORM\ManyToMany(targetEntity: Post::class, inversedBy: "relatedToPosts")]
    private Collection $relatedPosts;

    #[ORM\ManyToMany(targetEntity: Post::class, mappedBy: "relatedPosts")]
    private Collection $relatedToPosts;

    public function __construct(string $title, string $body, ?string $shortDescription = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->shortDescription = $shortDescription;
        $this->createdAt = new \DateTime('now');
        $this->tags = new ArrayCollection();
        $this->secondaryTags = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->relatedPosts = new ArrayCollection();
        $this->relatedToPosts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBody(): string
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

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
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

    public function isPublished(): bool
    {
        return null !== $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $timestamp): void
    {
        $this->publishedAt = $timestamp;
    }

    public function getRelatedPosts(): Collection
    {
        return $this->relatedPosts;
    }

    public function addRelatedPost(self $relatedPost): void
    {
        if (!$this->relatedPosts->contains($relatedPost)) {
            $this->relatedPosts[] = $relatedPost;
        }
    }

    public function removeRelatedPost(self $relatedPost): void
    {
        if ($this->relatedPosts->contains($relatedPost)) {
            $this->relatedPosts->removeElement($relatedPost);
        }
    }

    public function getRelatedToPosts(): Collection
    {
        return $this->relatedToPosts;
    }

    public function addRelatedToPost(self $relatedToPost): void
    {
        if (!$this->relatedToPosts->contains($relatedToPost)) {
            $this->relatedToPosts[] = $relatedToPost;
            $relatedToPost->addRelatedPost($this);
        }
    }

    public function removeRelatedToPost(self $relatedToPost): void
    {
        if ($this->relatedToPosts->contains($relatedToPost)) {
            $this->relatedToPosts->removeElement($relatedToPost);
            $relatedToPost->removeRelatedPost($this);
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

    public function getComments(): Collection
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

    public function setMostRelevantRelatedPost(?self $mostRelevantRelatedPost): void
    {
        $this->mostRelevantRelatedPost = $mostRelevantRelatedPost;
    }

    public function getMostRelevantRelatedToPost(): ?self
    {
        return $this->mostRelevantRelatedToPost;
    }

    public function setMostRelevantRelatedToPost(?self $mostRelevantRelatedToPost): void
    {
        $this->mostRelevantRelatedToPost = $mostRelevantRelatedToPost;
    }

    public function getLessRelevantRelatedPost(): ?self
    {
        return $this->lessRelevantRelatedPost;
    }

    public function setLessRelevantRelatedPost(?self $lessRelevantRelatedPost): void
    {
        $this->lessRelevantRelatedPost = $lessRelevantRelatedPost;
    }

    public function getLessRelevantRelatedToPost(): ?self
    {
        return $this->lessRelevantRelatedToPost;
    }

    public function setLessRelevantRelatedToPost(?self $lessRelevantRelatedToPost): void
    {
        $this->lessRelevantRelatedToPost = $lessRelevantRelatedToPost;
    }
}
