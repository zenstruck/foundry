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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipWithGlobalEntity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
class RelationshipWithGlobalEntity extends Base
{
    #[ORM\ManyToOne(targetEntity: GlobalEntity::class)]
    protected ?GlobalEntity $globalEntity = null;

    public function setGlobalEntity(?GlobalEntity $globalEntity): void
    {
        $this->globalEntity = $globalEntity;
    }

    public function getGlobalEntity(): ?GlobalEntity
    {
        return $this->globalEntity;
    }
}
