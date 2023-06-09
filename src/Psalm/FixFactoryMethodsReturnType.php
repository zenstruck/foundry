<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Psalm;

use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;

final class FixFactoryMethodsReturnType implements AfterMethodCallAnalysisInterface
{
    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void
    {
        [$class, $method] = explode('::', $event->getMethodId());

        //  PersistentObjectFactory::createOne() returns a list<Proxy<T>>
        //  ObjectFactory::createOne() returns a T
        if (is_subclass_of($class, ObjectFactory::class) && 'createone' === $method) {
            $templateType = $event->getCodebase()->classlikes->getStorageFor($class)->template_extended_params[ObjectFactory::class]['T'] ?? null;

            if (!$templateType) {
                return;
            }

            $event->setReturnTypeCandidate(
                match(is_subclass_of($class, PersistentObjectFactory::class)){
                    true => PsalmTypeHelper::genericTypeFromUnionType(Proxy::class, $templateType),
                    false => $templateType
                }
            );
        }

        //  PersistentObjectFactory->many() returns a FactoryCollection<Proxy<T>>
        if (is_subclass_of($class, PersistentObjectFactory::class) && (\in_array($method, ['many', 'sequence'], true))) {
            $templateType = $event->getCodebase()->classlikes->getStorageFor($class)->template_extended_params[PersistentObjectFactory::class]['T'] ?? null;

            if (!$templateType) {
                return;
            }

            $event->setReturnTypeCandidate(PsalmTypeHelper::factoryCollection($templateType));
        }
    }
}
