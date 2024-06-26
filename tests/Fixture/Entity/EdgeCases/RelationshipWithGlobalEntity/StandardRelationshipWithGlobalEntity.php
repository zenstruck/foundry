<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipWithGlobalEntity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
class StandardRelationshipWithGlobalEntity extends RelationshipWithGlobalEntity
{
    #[ORM\ManyToOne(targetEntity: GlobalEntity::class)]
    protected ?GlobalEntity $globalEntity = null;
}
