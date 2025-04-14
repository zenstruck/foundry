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
use Zenstruck\Foundry\Attribute\AsFoundryHook;
use Zenstruck\Foundry\Object\Event\AfterInstantiate;
use Zenstruck\Foundry\Object\Event\BeforeInstantiate;
use Zenstruck\Foundry\Object\Event\Event;
use Zenstruck\Foundry\Persistence\Event\AfterPersist;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityForEventListeners;

final class FoundryEventListener
{
    /** @param BeforeInstantiate<object> $event */
    #[AsEventListener]
    public function beforeInstantiate(BeforeInstantiate $event): void
    {
        if (EntityForEventListeners::class !== $event->objectClass) {
            return;
        }

        $event->parameters['name'] = $this->name($event->parameters['name'], $event);
    }

    /** @param AfterInstantiate<object> $event */
    #[AsEventListener]
    public function afterInstantiate(AfterInstantiate $event): void
    {
        if (!$event->object instanceof EntityForEventListeners) {
            return;
        }

        $event->object->name = $this->name($event->object->name, $event);
    }

    /** @param AfterPersist<object> $event */
    #[AsEventListener]
    public function afterPersist(AfterPersist $event): void
    {
        if (!$event->object instanceof EntityForEventListeners) {
            return;
        }

        $event->object->name = $this->name($event->object->name, $event);
    }

    /** @param BeforeInstantiate<EntityForEventListeners> $event */
    #[AsFoundryHook(EntityForEventListeners::class)]
    public function beforeInstantiateWithFoundryAttribute(BeforeInstantiate $event): void
    {
        $event->parameters['name'] = "{$this->name($event->parameters['name'], $event)} with Foundry attribute";
    }

    /** @param AfterInstantiate<EntityForEventListeners> $event */
    #[AsFoundryHook(EntityForEventListeners::class)]
    public function afterInstantiateWithFoundryAttribute(AfterInstantiate $event): void
    {
        $event->object->name = "{$this->name($event->object->name, $event)} with Foundry attribute";
    }

    /** @param AfterPersist<EntityForEventListeners> $event */
    #[AsFoundryHook(EntityForEventListeners::class)]
    public function afterPersistWithFoundryAttribute(AfterPersist $event): void
    {
        $event->object->name = "{$this->name($event->object->name, $event)} with Foundry attribute";
    }

    /** @param BeforeInstantiate<object> $event */
    #[AsFoundryHook()]
    public function globalBeforeInstantiate(BeforeInstantiate $event): void
    {
        if (EntityForEventListeners::class !== $event->objectClass) {
            return;
        }

        $event->parameters['name'] = "{$this->name($event->parameters['name'], $event)} global";
    }

    /** @param AfterInstantiate<object> $event */
    #[AsFoundryHook()]
    public function globalAfterInstantiate(AfterInstantiate $event): void
    {
        if (!$event->object instanceof EntityForEventListeners) {
            return;
        }

        $event->object->name = "{$this->name($event->object->name, $event)} global";
    }

    /** @param AfterPersist<object> $event */
    #[AsFoundryHook()]
    public function globalAfterPersist(AfterPersist $event): void
    {
        if (!$event->object instanceof EntityForEventListeners) {
            return;
        }

        $event->object->name = "{$this->name($event->object->name, $event)} global";
    }

    private function name(string $name, Event $event): string // @phpstan-ignore missingType.generics
    {
        $eventName = (new \ReflectionClass($event))->getShortName();

        return "{$name}\n{$eventName}";
    }
}
