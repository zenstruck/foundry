<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;

#[ORM\Embeddable]
class NestedEmbeddable
{
    #[ORM\Embedded(class: Embeddable::class)]
    private Embeddable $embeddable;

    public function __construct(Embeddable $embeddable)
    {
        $this->embeddable = $embeddable;
    }

    public function getEmbeddable(): Embeddable
    {
        return $this->embeddable;
    }
}
