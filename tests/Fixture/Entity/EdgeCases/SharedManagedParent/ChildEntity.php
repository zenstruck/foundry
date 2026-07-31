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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\SharedManagedParent;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('shared_managed_parent_child')]
#[ORM\HasLifecycleCallbacks]
class ChildEntity extends Base
{
    #[ORM\Column]
    public int $prePersistCount = 0;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: ParentEntity::class, inversedBy: 'children')]
        public ?ParentEntity $parent = null,
        #[ORM\ManyToOne(targetEntity: RootEntity::class, inversedBy: 'items')]
        public ?RootEntity $root = null,
    ) {
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        ++$this->prePersistCount;
    }
}
