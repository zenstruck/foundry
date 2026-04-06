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

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Zenstruck\Foundry\Tests\Fixture\Entity\ChildEntityForDoctrineEvents;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityForDoctrineEvents;
use Zenstruck\Foundry\Tests\Fixture\Entity\ParentEntityForDoctrineEvents;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class DoctrineEventsSubscriber
{
    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();

        if ($object instanceof EntityForDoctrineEvents
            || $object instanceof ParentEntityForDoctrineEvents
            || $object instanceof ChildEntityForDoctrineEvents
        ) {
            $object->name .= ' (from Doctrine event)';
        }
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();

        if (!$object instanceof EntityForDoctrineEvents) {
            return;
        }

        $eventArgs->setNewValue('name', $object->name . ' (from Doctrine preUpdate event)');
    }
}
