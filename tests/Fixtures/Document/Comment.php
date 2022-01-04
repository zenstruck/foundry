<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 */
class Comment
{
    /**
     * @MongoDB\EmbedOne(
     *     targetDocument=User::class
     * )
     */
    private $user;

    /**
     * @MongoDB\Field(type="string")
     */
    private $body;

    /**
     * @MongoDB\Field(type="date")
     */
    private $createdAt;

    /**
     * @MongoDB\Field(type="boolean")
     */
    private $approved = false;

    public function __construct(User $user, string $body)
    {
        $this->user = $user;
        $this->body = $body;
        $this->createdAt = new \DateTime('now');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getApproved(): bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): self
    {
        $this->approved = $approved;

        return $this;
    }
}
