<?php

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('entity_with_clone_method')]
class EntityWithCloneMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int|null $id = null;

    #[ORM\Column(nullable: true)]
    public string|null $prop = null;

    public function __clone()
    {
        $this->id = null;
    }
}
