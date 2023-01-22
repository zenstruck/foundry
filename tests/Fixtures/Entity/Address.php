<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Address
{
    /**
     * @var mixed|null
     */
    #[ORM\Column(type: "string", nullable: true)]
    private $value;

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): void
    {
        $this->value = $value;
    }
}
