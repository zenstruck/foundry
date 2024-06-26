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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RichDomainMandatoryRelationship;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CascadeOwningSideEntity extends OwningSideEntity
{
    #[ORM\ManyToOne(targetEntity: CascadeInversedSideEntity::class, cascade: ['persist'], inversedBy: 'relations')]
    protected InversedSideEntity $main;

    public function __construct(
        InversedSideEntity $main,
    ) {
        parent::__construct($main);
    }
}
