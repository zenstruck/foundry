<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
class WithUniqueColumn extends Base
{
    #[ORM\Column(unique: true)]
    private int $uniqueCol;

    public function __construct(int $uniqueCol)
    {
        $this->uniqueCol = $uniqueCol;
    }

    public function getUniqueCol(): int
    {
        return $this->uniqueCol;
    }

    public function setUniqueCol(int $uniqueCol): static
    {
        $this->uniqueCol = $uniqueCol;

        return $this;
    }
}
