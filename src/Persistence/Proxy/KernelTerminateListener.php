<?php

namespace Zenstruck\Foundry\Persistence\Proxy;

use Symfony\Component\HttpKernel\Event\TerminateEvent;

final class KernelTerminateListener
{
    public function __invoke(TerminateEvent $event): void
    {
        CreatedObjectsTracker::proxifyObjects();
    }
}
