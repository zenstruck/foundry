<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\EntityWithReadonly;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;

#[ORM\Entity]
#[ORM\Table()]
class EntityWithReadonly
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    public function __construct(
        #[ORM\Column()]
        public readonly int $prop,

        #[ORM\Embedded(class: Embeddable::class)]
        public readonly Embeddable $embedded,

        #[ORM\Column()]
        public readonly \DateTimeImmutable $date,
    )
    {
    }
}
