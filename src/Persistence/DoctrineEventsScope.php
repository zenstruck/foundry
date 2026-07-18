<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

/**
 * Accumulates the listener classes disabled during a single root create()
 * operation and the corresponding restorations, replayed after the root flush.
 * Lives as a local variable of the root create() call and is only referenced
 * by short-lived factory clones — never stored on a long-lived service.
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @internal
 */
final class DoctrineEventsScope
{
    /** @var list<class-string>|null null = disabling not requested, [] = disable all */
    private ?array $disabledClasses = null;

    /** @var list<callable():void> */
    private array $restorers = [];

    /** @var array<string, true> */
    private array $registered = [];

    private bool $open = true;

    public function __construct(private readonly PersistenceManager $persistenceManager)
    {
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * Merges the given listener classes into the scope, then disables all the
     * accumulated classes for the given entity class.
     *
     * @param class-string            $entityClass
     * @param list<class-string>|null $disabledClasses null = nothing requested by this factory, [] = all
     */
    public function disable(string $entityClass, ?array $disabledClasses): void
    {
        $this->disabledClasses = match (true) {
            null === $disabledClasses => $this->disabledClasses,
            null === $this->disabledClasses => $disabledClasses,
            [] === $disabledClasses || [] === $this->disabledClasses => [],
            default => \array_values(\array_unique([...$this->disabledClasses, ...$disabledClasses])),
        };

        if (null === $this->disabledClasses) {
            return;
        }

        $sortedClasses = $this->disabledClasses;
        \sort($sortedClasses);
        $key = "{$entityClass}\0".\implode("\0", $sortedClasses);

        if (isset($this->registered[$key])) {
            return;
        }

        $this->restorers[] = $this->persistenceManager->disableDoctrineEvents($entityClass, $this->disabledClasses);
        // marked only after success, so a failed registration can be retried
        $this->registered[$key] = true;
    }

    public function close(): void
    {
        $this->open = false;

        // reverse order: when the same class was disabled twice with different lists,
        // restoration must go through the intermediate state before the original one
        foreach (\array_reverse($this->restorers) as $restore) {
            $restore();
        }

        $this->restorers = $this->registered = [];
    }
}
