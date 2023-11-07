<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SpecificPost extends Post
{
    /**
     * @var mixed|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $specificProperty;

    public function getSpecificProperty()
    {
        return $this->specificProperty;
    }

    public function setSpecificProperty($specificProperty): static
    {
        $this->specificProperty = $specificProperty;

        return $this;
    }
}
