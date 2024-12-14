<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;

#[MongoDB\Document]
class DocumentWithReadonly extends Base
{
    public function __construct(
        #[MongoDB\Field()]
        public readonly int $prop,

        #[MongoDB\EmbedOne(targetDocument: Embeddable::class)]
        public readonly Embeddable $embedded,

        #[MongoDB\Field()]
        public readonly \DateTimeImmutable $date,
    )
    {
    }
}
