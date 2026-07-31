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

use Doctrine\Common\EventManager;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Foundry\Configuration;

/**
 * Initializes the tracked lazy ghosts still managed by the flushing object manager, right
 * before it computes changesets: ORM/ODM versions unaware of PHP native lazy objects read
 * properties through an `(array)` cast which does not trigger the ghost initialization,
 * and would compute an all-null changeset, erasing the object's data in the database.
 *
 * @see https://github.com/zenstruck/foundry/issues/1122
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @internal
 */
final class InitializeTrackedGhostsBeforeFlushListener
{
    /**
     * Event managers already guarded by this listener. Static on purpose: idempotence must
     * survive strategy re-instantiation across kernel resets, while the event manager
     * (owned by the connection) is longer-lived than the object manager itself.
     *
     * @var \WeakMap<EventManager, true>
     */
    private static \WeakMap $registeredEventManagers;

    public static function registerTo(EventManager $eventManager, string $eventName): void
    {
        if (!isset(self::$registeredEventManagers)) {
            /** @var \WeakMap<EventManager, true> $registeredEventManagers */
            $registeredEventManagers = new \WeakMap();
            self::$registeredEventManagers = $registeredEventManagers;
        }

        if (isset(self::$registeredEventManagers[$eventManager])) {
            return;
        }

        $eventManager->addEventListener([$eventName], new self());
        self::$registeredEventManagers[$eventManager] = true;
    }

    /**
     * @param ManagerEventArgs<ObjectManager> $args
     */
    public function preFlush(ManagerEventArgs $args): void
    {
        if (!Configuration::isBooted()) {
            return;
        }

        $om = $args->getObjectManager();

        foreach (PersistedObjectsTracker::trackedObjects() as $object) {
            $reflector = new \ReflectionClass($object);

            if (!$reflector->isUninitializedLazyObject($object)) {
                continue;
            }

            // contains() only checks the identity map: it does not initialize the ghost
            if (!$om->contains($object)) {
                continue;
            }

            $reflector->initializeLazyObject($object);
        }
    }
}
