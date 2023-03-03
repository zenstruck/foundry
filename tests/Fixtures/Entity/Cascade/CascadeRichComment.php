<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class CascadeRichComment
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
     * @ORM\ManyToOne(targetEntity=CascadeRichPost::class, inversedBy="comments", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private CascadeRichPost $post;

    public function __construct(string $body, CascadeRichPost $post)
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

    public function getPost(): CascadeRichPost
    {
        return $this->post;
    }
}
