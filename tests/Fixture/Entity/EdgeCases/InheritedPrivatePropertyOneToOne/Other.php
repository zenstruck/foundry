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

#[ORM\Entity]
class Other
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'owning')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Child $inverse = null;

    #[ORM\OneToOne(mappedBy: 'inverse')]
    private ?Child $owning = null;

    public function setInverse(?Child $inverse): void
    {
        $this->inverse = $inverse;
    }

    public function setOwning(?Child $owning): void
    {
        $this->owning = $owning;
    }
}
