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

namespace Zenstruck\Foundry\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class EntityForValidation extends Base
{
    public function __construct(
        #[ORM\Column()]
        #[Assert\NotBlank()]
        public string $name = '',

        #[ORM\Column()]
        #[Assert\GreaterThan(10, groups: ['validation_group'])]
        public int $number = 0,
    ) {
    }
}
