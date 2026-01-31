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

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\ManyToOneWithCascade;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('many_to_one_with_cascade_owning_side')]
class OwningSide
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    public Uuid $id;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    public InverseSide $inverseSide;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->inverseSide = new InverseSide();
    }
}
