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
    private string $uniqueCol;

    public function __construct(string $uniqueCol)
    {
        $this->uniqueCol = $uniqueCol;
    }

    public function getUniqueCol(): string
    {
        return $this->uniqueCol;
    }

    public function setUniqueCol(string $uniqueCol): static
    {
        $this->uniqueCol = $uniqueCol;

        return $this;
    }
}
