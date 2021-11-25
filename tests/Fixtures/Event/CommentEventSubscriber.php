<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Comment;

class CommentEventSubscriber implements EventSubscriber
{
    public const COMMENT_BODY = 'test-event';
    public const NEW_COMMENT_BODY = 'new body';

    public function getSubscribedEvents(): array
    {
        return [Events::prePersist];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        if (
            !$event->getObject() instanceof Comment
            || self::COMMENT_BODY !== $event->getObject()->getBody()
        ) {
            return;
        }

        $event->getObject()->setBody(self::NEW_COMMENT_BODY);
    }
}
