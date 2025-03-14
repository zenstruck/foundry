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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\OneToManyWithUnionType;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
class HasOneToManyWithUnionType extends Base
{
    public function __construct(
        /** @var Collection<int,OwningSideEntity>|list<OwningSideEntity> */
        #[ORM\OneToMany(targetEntity: OwningSideEntity::class, mappedBy: 'item')] // @phpstan-ignore doctrine.associationType, doctrine.associationType
        public Collection|array $collection,
    ) {
    }
}
