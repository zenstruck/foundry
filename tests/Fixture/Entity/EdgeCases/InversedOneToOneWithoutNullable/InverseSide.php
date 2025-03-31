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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InversedOneToOneWithoutNullable;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('inversed_one_to_one_without_nullable_inverse_side')]
class InverseSide extends Base
{
    public function __construct(// @phpstan-ignore missingType.parameter
        #[ORM\OneToOne(mappedBy: 'inverseSide')] // @phpstan-ignore doctrine.associationType
        public OwningSide $owningSide,

        #[ORM\OneToOne(targetEntity: OwningSide::class, mappedBy: 'inverseSideNotTyped')]
        public $owningSideNotTyped,
    ) {
    }
}
