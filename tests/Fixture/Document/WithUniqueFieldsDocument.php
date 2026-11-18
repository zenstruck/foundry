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
use Zenstruck\Foundry\Tests\Fixture\Model\WithUniqueFields;

#[MongoDB\Document]
#[MongoDB\UniqueIndex(keys: ['uniqueByFieldName' => 'asc'])]
#[MongoDB\UniqueIndex(keys: ['unique_by_column_name' => 'asc'])]
#[MongoDB\UniqueIndex(keys: ['uniqueTogether1' => 'asc', 'uniqueTogether2' => 'asc'])]
class WithUniqueFieldsDocument extends WithUniqueFields
{
}
