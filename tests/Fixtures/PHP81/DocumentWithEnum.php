<?php

declare(strict_types=1);

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

#[MongoDB\Document(collection: 'document-with-enum')]
class DocumentWithEnum
{
    #[MongoDB\Field(enumType: SomeEnum::class)]
    public SomeEnum $enum;
    #[MongoDB\Id]
    private $id;
}
