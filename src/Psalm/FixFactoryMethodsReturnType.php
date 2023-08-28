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

namespace Zenstruck\Foundry\Psalm;

use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Zenstruck\Foundry\Object\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @internal
 */
final class FixFactoryMethodsReturnType implements AfterMethodCallAnalysisInterface
{
    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void
    {
        [$class, $method] = \explode('::', $event->getMethodId());

        //  PersistentObjectFactory::createOne() returns a list<Proxy<T>>
        //  ObjectFactory::createOne() returns a T
        if (PsalmTypeHelper::isSubClassOf($class, ObjectFactory::class) && 'createone' === $method) {
            $templateType = $event->getCodebase()->classlikes->getStorageFor($class)->template_extended_params[ObjectFactory::class]['T'] ?? null;

            if (!$templateType) {
                return;
            }

            $event->setReturnTypeCandidate(
                match (PsalmTypeHelper::isSubClassOf($class, PersistentObjectFactory::class)) {
                    true => PsalmTypeHelper::genericTypeFromUnionType(Proxy::class, $templateType),
                    false => $templateType
                }
            );
        }

        //  PersistentObjectFactory->many() returns a FactoryCollection<Proxy<T>>
        if (PsalmTypeHelper::isSubClassOf($class, PersistentObjectFactory::class) && \in_array($method, ['many', 'sequence'], true)) {
            $templateType = $event->getCodebase()->classlikes->getStorageFor($class)->template_extended_params[PersistentObjectFactory::class]['T'] ?? null;

            if (!$templateType) {
                return;
            }

            $event->setReturnTypeCandidate(PsalmTypeHelper::factoryCollection($templateType));
        }
    }
}
