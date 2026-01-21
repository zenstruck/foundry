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

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\ChangeFactoryBaseClassRector;
use Zenstruck\Foundry\Utils\Rector\ChangeProxyParamTypesRector;
use Zenstruck\Foundry\Utils\Rector\ChangeProxyReturnTypesRector;
use Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWithObjectAsFirstParameter\MethodCallToFuncCallWithObjectAsFirstParameter;
use Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWithObjectAsFirstParameter\MethodCallToFuncCallWithObjectAsFirstParameterRector;
use Zenstruck\Foundry\Utils\Rector\RemoveFunctionCall\RemoveFunctionCall;
use Zenstruck\Foundry\Utils\Rector\RemoveFunctionCall\RemoveFunctionCallRector;
use Zenstruck\Foundry\Utils\Rector\RemoveMethodCall\RemoveMethodCall;
use Zenstruck\Foundry\Utils\Rector\RemoveMethodCall\RemoveMethodCallRector;
use Zenstruck\Foundry\Utils\Rector\RemovePhpDocProxyTypeHintRector;
use Zenstruck\Foundry\Utils\Rector\RemoveUnproxifyArrayMapRector;
use Zenstruck\Foundry\Utils\Rector\RemoveWithoutAutorefreshCallRector;

return static function(RectorConfig $rectorConfig): void {
    if (\PHP_VERSION_ID < 80400) {
        throw new LogicException('Cannot use Foundry rector suite with PHP < 8.4');
    }

    $rectorConfig->ruleWithConfiguration(
        MethodCallToFuncCallWithObjectAsFirstParameterRector::class,
        [
            new MethodCallToFuncCallWithObjectAsFirstParameter('_get', 'Zenstruck\Foundry\get'),
            new MethodCallToFuncCallWithObjectAsFirstParameter('_set', 'Zenstruck\Foundry\set'),

            new MethodCallToFuncCallWithObjectAsFirstParameter('_save', 'Zenstruck\Foundry\Persistence\save'),
            new MethodCallToFuncCallWithObjectAsFirstParameter('_refresh', 'Zenstruck\Foundry\Persistence\refresh'),
            new MethodCallToFuncCallWithObjectAsFirstParameter('_delete', 'Zenstruck\Foundry\Persistence\delete'),

            new MethodCallToFuncCallWithObjectAsFirstParameter('_assertPersisted', 'Zenstruck\Foundry\Persistence\assert_persisted'),
            new MethodCallToFuncCallWithObjectAsFirstParameter('_assertNotPersisted', 'Zenstruck\Foundry\Persistence\assert_not_persisted'),

            new MethodCallToFuncCallWithObjectAsFirstParameter('_repository', 'Zenstruck\Foundry\Persistence\repository'),
        ]
    );

    $rectorConfig->ruleWithConfiguration(
        RemoveMethodCallRector::class,
        [
            new RemoveMethodCall('_enableAutoRefresh'),
            new RemoveMethodCall('_disableAutoRefresh'),
            new RemoveMethodCall('_real'),
        ]
    );

    $rectorConfig->ruleWithConfiguration(
        RemoveFunctionCallRector::class,
        [
            new RemoveFunctionCall('Zenstruck\Foundry\Persistence\proxy'),
            new RemoveFunctionCall('Zenstruck\Foundry\Persistence\unproxy'),
        ]
    );

    $rectorConfig->rules([
        RemoveWithoutAutorefreshCallRector::class,
        ChangeFactoryBaseClassRector::class,
        ChangeProxyParamTypesRector::class,
        ChangeProxyReturnTypesRector::class,
        RemovePhpDocProxyTypeHintRector::class,
        RemoveUnproxifyArrayMapRector::class,
    ]);
};
