<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;

class PostEventSubscriber implements EventSubscriber
{
    public const TITLE_VALUE = 'title value from event subscriber';
    public const NEW_TITLE_VALUE = 'new title value from event subscriber';

    public function getSubscribedEvents(): array
    {
        return [Events::prePersist];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        if (
            !$event->getObject() instanceof Post
            || self::TITLE_VALUE !== $event->getObject()->getTitle()
        ) {
            return;
        }

        $event->getObject()->setTitle(self::NEW_TITLE_VALUE);
    }
}
