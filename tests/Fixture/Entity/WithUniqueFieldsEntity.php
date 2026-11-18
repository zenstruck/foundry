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
use Zenstruck\Foundry\Tests\Fixture\Model\WithUniqueFields;

#[ORM\Entity]
#[ORM\UniqueConstraint(fields: ['uniqueByFieldName'])]
#[ORM\UniqueConstraint(columns: ['unique_by_column_name'])]
#[ORM\UniqueConstraint(fields: ['uniqueTogether1', 'uniqueTogether2'])]
class WithUniqueFieldsEntity extends WithUniqueFields
{
}
