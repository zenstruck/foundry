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

namespace Zenstruck\Foundry\Object\Event;

final class HookListenerFilter
{
    /** @var \Closure(Event<object>): void */
    private \Closure $listener;

    /**
     * @param array{0: object, 1: string} $listener
     * @param class-string|null           $objectClass
     */
    public function __construct(array $listener, private ?string $objectClass = null)
    {
        if (!\is_callable($listener)) {
            throw new \InvalidArgumentException(\sprintf('Listener must be a callable, "%s" given.', \get_debug_type($listener)));
        }

        $this->listener = $listener(...);
    }

    /**
     * @param Event<object> $event
     */
    public function __invoke(Event $event): void
    {
        if ($this->objectClass && $event->objectClassName() !== $this->objectClass) {
            return;
        }

        ($this->listener)($event);
    }
}
