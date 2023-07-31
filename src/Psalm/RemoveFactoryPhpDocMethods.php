<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Psalm;

use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Let's make Psalm forget about `@method` in factories PHPDoc
 */
final class RemoveFactoryPhpDocMethods implements AfterClassLikeVisitInterface
{
    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void
    {
        $classLikeStorage = $event->getStorage();

        if ($classLikeStorage->parent_class === PersistentObjectFactory::class
            || is_subclass_of($classLikeStorage->name, PersistentObjectFactory::class)) {
            foreach (array_keys($classLikeStorage->pseudo_methods) as $name) {
                if (method_exists(PersistentObjectFactory::class, $name)) {
                    unset($classLikeStorage->pseudo_methods[$name]);
                }
            }

            foreach (array_keys($classLikeStorage->pseudo_static_methods) as $name) {
                if (method_exists(PersistentObjectFactory::class, $name)) {
                    unset($classLikeStorage->pseudo_static_methods[$name]);
                }
            }
        }
    }
}
