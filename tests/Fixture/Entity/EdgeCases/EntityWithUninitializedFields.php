<?php

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
class EntityWithUninitializedFields extends Base
{
    #[ORM\Column(nullable: true)]
    private ?int $uninitializedField; // @phpstan-ignore property.uninitialized

    public function getUninitializedField(): ?int
    {
        return $this->uninitializedField;
    }
}
