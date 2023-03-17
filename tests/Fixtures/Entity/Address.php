<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Address
{
    public function __construct(
        #[ORM\Column(type: "string", nullable: true)]
        private string $value
    )
    {
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
