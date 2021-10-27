<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity=Role::class, mappedBy="user")
     */
    private $role;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setRole(Role $role)
    {
        $this->role = $role;

        if ($role->getUser() !== $this) {
            $role->setUser($this);
        }
    }

    public function getRole()
    {
        return $this->role;
    }
}
