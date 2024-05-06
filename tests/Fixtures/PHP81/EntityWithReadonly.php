<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\PHP81;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;

#[ORM\Entity]
#[ORM\Table()]
class EntityWithReadonly
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    public function __construct(
        #[ORM\Column()]
        public readonly int $prop,

        #[ORM\Embedded(Address::class)]
        public readonly Address $embed,

        #[ORM\Column()]
        public readonly \DateTimeImmutable $date,
    )
    {
    }
}
