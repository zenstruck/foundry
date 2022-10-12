<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class RichComment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="text")
     */
    private string $body;

    /**
     * @ORM\ManyToOne(targetEntity=RichPost::class, inversedBy="comments")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private RichPost $post;

    public function __construct(string $body, RichPost $post)
    {
        $this->body = $body;
        $this->post = $post;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getPost(): RichPost
    {
        return $this->post;
    }
}
