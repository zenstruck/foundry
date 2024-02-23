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
use Zenstruck\Foundry\Tests\Fixture\Entity\Repository\GenericEntityRepository;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[ORM\Entity(repositoryClass: GenericEntityRepository::class)]
class GenericEntity extends GenericModel
{
}
