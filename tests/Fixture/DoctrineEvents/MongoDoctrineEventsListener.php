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

use Doctrine\Bundle\MongoDBBundle\Attribute\AsDocumentListener;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentForDoctrineEvents;

#[AsDocumentListener(event: Events::prePersist)]
final class MongoDoctrineEventsListener
{
    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $object = $eventArgs->getDocument();

        if (!$object instanceof DocumentForDoctrineEvents) {
            return;
        }

        $object->name .= ' (from Mongo event)';
    }
}
