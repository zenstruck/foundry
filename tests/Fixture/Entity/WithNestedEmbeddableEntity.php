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
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
class WithNestedEmbeddableEntity extends Base
{
    #[ORM\Embedded(class: NestedEmbeddable::class)]
    private NestedEmbeddable $nestedEmbeddable;

    public function __construct(NestedEmbeddable $nestedEmbeddable)
    {
        $this->nestedEmbeddable = $nestedEmbeddable;
    }

    public function getNestedEmbeddable(): NestedEmbeddable
    {
        return $this->nestedEmbeddable;
    }
}
