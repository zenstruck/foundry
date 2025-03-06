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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InversedOneToOneWithManyToOne;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('inversed_one_to_one_with_many_to_one_inverse_side')]
class InverseSide extends Base
{
    #[ORM\OneToOne(mappedBy: 'inverseSide')]
    public ?OwningSide $owningSide = null;

    #[ORM\ManyToOne()]
    public ?Item $item = null;

    public function __construct(
        #[ORM\Column()]
        public string $mandatoryField,
    ) {
    }
}
