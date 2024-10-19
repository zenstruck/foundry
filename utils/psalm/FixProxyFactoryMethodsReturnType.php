<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Psalm;

use Doctrine\Persistence\ObjectRepository;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\Type;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

final class FixProxyFactoryMethodsReturnType implements AfterMethodCallAnalysisInterface
{
    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void
    {
        [$class, $method] = explode('::', $event->getMethodId());

        if ($event->getCodebase()->classExtends($class, PersistentProxyObjectFactory::class)) {
            $templateType = $event->getCodebase()->classlikes->getStorageFor($class)->template_extended_params[PersistentProxyObjectFactory::class]['T'] ?? null;

            if (!$templateType) {
                return;
            }

            $templateTypeAsString = $templateType->getId();
            $proxyTypeHint = "{$templateTypeAsString}&Zenstruck\\Foundry\\Persistence\\Proxy<{$templateTypeAsString}>";

            $methodsReturningObject = ['create', 'createone', 'first', 'last', 'find', 'random', 'findorcreate', 'randomorcreate'];
            if (\in_array($method, $methodsReturningObject, true)) {
                $event->setReturnTypeCandidate(Type::parseString($proxyTypeHint));
            }

            $methodsReturningListOfObjects = ['createmany', 'randomrange', 'randomset', 'findby', 'all', 'createsequence'];
            if (\in_array($method, $methodsReturningListOfObjects, true)) {
                $event->setReturnTypeCandidate(Type::parseString("list<{$proxyTypeHint}>"));
            }

            $methodsReturningFactoryCollection = ['many', 'sequence'];
            if (\in_array($method, $methodsReturningFactoryCollection, true)) {
                $factoryCollectionClass = FactoryCollection::class;
                $event->setReturnTypeCandidate(Type::parseString("{$factoryCollectionClass}<{$proxyTypeHint}>"));
            }

            if ($method === 'repository'
                // if user has overridden the repository() method, we should not change the return type
                && str_starts_with($event->getReturnTypeCandidate()->getId(), RepositoryDecorator::class)
            ) {
                $repositoryDecoratorClass = RepositoryDecorator::class;
                $doctrineRepositoryClass = ObjectRepository::class;
                $event->setReturnTypeCandidate(Type::parseString("{$repositoryDecoratorClass}<{$proxyTypeHint}, {$doctrineRepositoryClass}<$templateTypeAsString>>"));
            }
        }
    }
}
