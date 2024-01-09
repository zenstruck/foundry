<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Contract\MethodCallRenameInterface;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Transform\Rector\MethodCall\MethodCallToPropertyFetchRector;
use Rector\Transform\ValueObject\MethodCallToPropertyFetch;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Utils\Rector\AddProxyToFactoryCollectionTypeInPhpDoc;
use Zenstruck\Foundry\Utils\Rector\ChangeDisableEnablePersist;
use Zenstruck\Foundry\Utils\Rector\ChangeFactoryBaseClass;
use Zenstruck\Foundry\Utils\Rector\ChangeFactoryMethodCalls;
use Zenstruck\Foundry\Utils\Rector\ChangeFunctionsCalls;
use Zenstruck\Foundry\Utils\Rector\ChangeInstantiatorMethodCalls;
use Zenstruck\Foundry\Utils\Rector\ChangeLegacyClassImports;
use Zenstruck\Foundry\Utils\Rector\ChangeProxyMethodCalls;
use Zenstruck\Foundry\Utils\Rector\ChangeStaticFactoryFakerCalls;
use Zenstruck\Foundry\Utils\Rector\PersistenceResolver;
use Zenstruck\Foundry\Utils\Rector\RemoveProxyRealObjectMethodCallsForNotProxifiedObjects;
use Zenstruck\Foundry\Utils\Rector\RuleRequirementsChecker;

return static function (RectorConfig $rectorConfig): void {
    RuleRequirementsChecker::checkRequirements();

    /**
     * This must be overridden in user's `rector.php` to provide a path to the object manager
     * @see https://github.com/phpstan/phpstan-doctrine#configuration
     */
    $rectorConfig->singleton(PersistenceResolver::class);

    $rectorConfig->rules([
        ChangeFactoryBaseClass::class,
        ChangeLegacyClassImports::class,
        RemoveProxyRealObjectMethodCallsForNotProxifiedObjects::class,
        ChangeInstantiatorMethodCalls::class,
        ChangeDisableEnablePersist::class,
        AddProxyToFactoryCollectionTypeInPhpDoc::class,
        ChangeFactoryMethodCalls::class,
        ChangeFunctionsCalls::class,
        ChangeProxyMethodCalls::class,
        ChangeStaticFactoryFakerCalls::class,
    ]);

    $rectorConfig->ruleWithConfiguration(
        RenameMethodRector::class,
        [
            new MethodCallRename(FactoryCollection::class, 'set', 'many')
        ]
    );
};
