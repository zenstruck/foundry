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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\OneToManyWithLifecycleCallback;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
#[ORM\Table('one_to_many_lifecycle_child')]
class ChildEntity extends Base
{
    #[ORM\ManyToOne(inversedBy: 'children')]
    public ?ParentEntity $parent = null;

    #[ORM\Column(nullable: true)]
    public ?string $name = null;
}
