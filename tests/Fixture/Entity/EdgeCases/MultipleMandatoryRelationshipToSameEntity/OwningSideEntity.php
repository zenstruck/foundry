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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\MultipleMandatoryRelationshipToSameEntity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
class OwningSideEntity extends Base
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: InversedSideEntity::class, cascade: ['persist', 'remove'], inversedBy: 'mainRelations')]
        #[ORM\JoinColumn(nullable: false)]
        private InversedSideEntity $main,
        #[ORM\ManyToOne(targetEntity: InversedSideEntity::class, cascade: ['persist', 'remove'], inversedBy: 'secondaryRelations')]
        #[ORM\JoinColumn(nullable: false)]
        private InversedSideEntity $secondary,
    ) {
    }

    public function getMain(): InversedSideEntity
    {
        return $this->main;
    }

    public function setMain(InversedSideEntity $main): static
    {
        $this->main = $main;

        return $this;
    }

    public function getSecondary(): InversedSideEntity
    {
        return $this->secondary;
    }

    public function setSecondary(InversedSideEntity $secondary): void
    {
        $this->secondary = $secondary;
    }
}
