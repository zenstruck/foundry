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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InheritedPrivatePropertyOneToOne;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
class Ancestor
{
    #[ORM\OneToOne(mappedBy: 'inverse')]
    private ?Other $owning = null;

    #[ORM\OneToOne(inversedBy: 'owning')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Other $inverse = null;

    public function setInverse(?Other $inverse)
    {
        $this->inverse = $inverse;
    }

    public function setOwning(?Other $owning)
    {
        $this->owning = $owning;
    }
}
