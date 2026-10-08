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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\UninitializedOneToMany;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('uninitialized_one_to_many_parent')]
class ParentEntity extends Base
{
    /** @var Collection<int, Child> */
    #[ORM\OneToMany(targetEntity: Child::class, mappedBy: 'parent')]
    public Collection $items; // @phpstan-ignore property.uninitialized (the point of this edge case)
}
