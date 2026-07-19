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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\AccessorDirectionality;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('accessor_directionality_item')]
class ItemEntity extends Base
{
    #[ORM\ManyToOne(targetEntity: CategoryEntity::class, inversedBy: 'items')]
    private ?CategoryEntity $category = null;

    public int $setterCalls = 0;

    public function getCategory(): ?CategoryEntity
    {
        return $this->category;
    }

    public function setCategory(?CategoryEntity $category): void
    {
        ++$this->setterCalls;
        $this->category = $category;
    }
}
