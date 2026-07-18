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
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Zenstruck\Foundry\Tests\Fixture\Entity\ChildEntityWithCascadeToEntityListener;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityWithAsEntityListener;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: EntityWithAsEntityListener::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: EntityWithAsEntityListener::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist2', entity: ChildEntityWithCascadeToEntityListener::class)]
final class AsEntityListenerListener
{
    public static bool $postPersistExecuted = false;

    public function prePersist(EntityWithAsEntityListener $entity, PrePersistEventArgs $eventArgs): void
    {
        $entity->name .= ' (from AsEntityListener)';
    }

    public function postPersist(EntityWithAsEntityListener $entity): void
    {
        static::$postPersistExecuted = true;
    }

    public function postPersist2(ChildEntityWithCascadeToEntityListener $entity): void
    {
        static::$postPersistExecuted = true;
    }
}
