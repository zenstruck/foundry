<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipOnInterface;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
#[ORM\Table('relationship_on_interface_other_entity')]
class OtherEntity extends Base
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Entity::class)] // @phpstan-ignore doctrine.associationType
        #[ORM\JoinColumn(nullable: false)]
        public EntityInterface $entity,
    ) {
    }
}
