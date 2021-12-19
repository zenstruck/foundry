<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Event;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Comment;

class CommentEventListener
{
    public const COMMENT_BODY = 'test-event-listener';
    public const NEW_COMMENT_BODY = 'new body listener';

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
