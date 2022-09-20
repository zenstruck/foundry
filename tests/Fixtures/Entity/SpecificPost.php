<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class SpecificPost extends Post
{
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $specificProperty;

    public function getSpecificProperty()
    {
        return $this->specificProperty;
    }

    public function setSpecificProperty($specificProperty)
    {
        $this->specificProperty = $specificProperty;

        return $this;
    }
}
