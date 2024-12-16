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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InversedOneToOneWithSetter;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('inversed_one_to_one_with_setter_inverse_side')]
class InverseSide extends Base
{
    #[ORM\OneToOne(mappedBy: 'inverseSide')]
    private OwningSide|null $owningSide = null;

    public function getOwningSide(): ?OwningSide
    {
        return $this->owningSide;
    }

    public function setOwningSide(OwningSide $owningSide): void
    {
        $this->owningSide = $owningSide;
        $owningSide->inverseSide = $this;
    }
}
