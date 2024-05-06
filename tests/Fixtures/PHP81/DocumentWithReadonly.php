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

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;

#[MongoDB\Document(collection: 'document-with-readonly')]
class DocumentWithReadonly
{
    #[MongoDB\Id]
    private $id;

    public function __construct(
        #[MongoDB\Field()]
        public readonly int $prop,

        #[MongoDB\EmbedOne(targetDocument: ODMUser::class)]
        public readonly ODMUser $embed,

        #[MongoDB\Field()]
        public readonly \DateTimeImmutable $date,
    ) {
    }
}
