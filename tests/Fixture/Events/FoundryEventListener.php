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

namespace Zenstruck\Foundry\Tests\Fixture\Events;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Zenstruck\Foundry\Object\Event\AfterInstantiate;
use Zenstruck\Foundry\Object\Event\BeforeInstantiate;
use Zenstruck\Foundry\Persistence\Event\AfterPersist;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityForEventListeners;

final class FoundryEventListener
{
    #[AsEventListener]
    public function beforeInstantiate(BeforeInstantiate $event): void
    {
        if (EntityForEventListeners::class !== $event->objectClass) {
            return;
        }

        $event->parameters['name'] = "{$event->parameters['name']}\nBeforeInstantiate";
    }

    #[AsEventListener]
    public function afterInstantiate(AfterInstantiate $event): void
    {
        if (!$event->object instanceof EntityForEventListeners) {
            return;
        }

        $event->object->name = "{$event->object->name}\nAfterInstantiate";
    }

    #[AsEventListener]
    public function afterPersist(AfterPersist $event): void
    {
        if (!$event->object instanceof EntityForEventListeners) {
            return;
        }

        $event->object->name = "{$event->object->name}\nAfterPersist";
    }
}
