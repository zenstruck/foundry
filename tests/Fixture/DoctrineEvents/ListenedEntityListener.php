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

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineEvents;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Zenstruck\Foundry\Tests\Fixture\Entity\ListenedEntity;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: ListenedEntity::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: ListenedEntity::class)]
final class ListenedEntityListener
{
    public static int $postPersistCount = 0;

    public function prePersist(ListenedEntity $entity): void
    {
        $entity->name .= ' (from listener)';
    }

    public function postPersist(ListenedEntity $entity): void
    {
        ++self::$postPersistCount;
    }
}
