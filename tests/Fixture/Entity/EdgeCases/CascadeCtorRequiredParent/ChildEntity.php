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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\CascadeCtorRequiredParent;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * The parent is required by the constructor AND the ManyToOne cascades persist:
 * persisting the child may cascade to a parent Foundry did not persist itself yet.
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('cascade_ctor_required_child')]
class ChildEntity extends Base
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: ParentEntity::class, inversedBy: 'children', cascade: ['persist'])]
        #[ORM\JoinColumn(nullable: false)]
        public ParentEntity $parent,
    ) {
    }
}
