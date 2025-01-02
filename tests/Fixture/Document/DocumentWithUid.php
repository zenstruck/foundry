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
use Symfony\Component\Uid\Uuid;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[MongoDB\Document]
class DocumentWithUid
{
    #[MongoDB\Id(type: 'bin_uuid', strategy: 'NONE')]
    public string $id;

    public function __construct()
    {
        $this->id = Uuid::v7()->toBinary();
    }
}
