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

namespace Zenstruck\Foundry\Tests\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class DocumentForDoctrineEvents
{
    #[MongoDB\Id(type: 'int', strategy: 'INCREMENT')]
    public ?int $id = null;

    public function __construct(
        #[MongoDB\Field(type: 'string')]
        public string $name,
    ) {
    }
}
