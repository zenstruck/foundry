<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipWithGlobalEntity;

use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
class CascadeRelationshipWithGlobalEntity extends RelationshipWithGlobalEntity
{
    #[ORM\ManyToOne(targetEntity: GlobalEntity::class, cascade: ['persist'])]
    protected ?GlobalEntity $globalEntity = null;
}
