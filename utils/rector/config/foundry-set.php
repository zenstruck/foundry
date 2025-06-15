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
use Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWIthObjectASFirstParameter\MethodCallToFuncCallWithObjectAsFirstParameter;
use Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWIthObjectASFirstParameter\MethodCallToFuncCallWIthObjectAsFirstParameterRector;
use Zenstruck\Foundry\Utils\Rector\RemoveFunctionCall\RemoveFunctionCall;
use Zenstruck\Foundry\Utils\Rector\RemoveFunctionCall\RemoveFunctionCallRector;
use Zenstruck\Foundry\Utils\Rector\RemoveMethodCall\RemoveMethodCall;
use Zenstruck\Foundry\Utils\Rector\RemoveMethodCall\RemoveMethodCallRector;
use Zenstruck\Foundry\Utils\Rector\RemoveWithoutAutorefreshCallRector;

return static function(RectorConfig $rectorConfig): void {
//    if (\PHP_VERSION_ID < 80400) {
//        throw new \LogicException('Cannot use Foundry rector suite with PHP < 8.4');
//    }

    $rectorConfig->ruleWithConfiguration(
        MethodCallToFuncCallWIthObjectAsFirstParameterRector::class,
        [
            new MethodCallToFuncCallWithObjectAsFirstParameter('_get', 'Zenstruck\Foundry\get'),
            new MethodCallToFuncCallWithObjectAsFirstParameter('_set', 'Zenstruck\Foundry\set'),

            new MethodCallToFuncCallWithObjectAsFirstParameter('_save', 'Zenstruck\Foundry\Persistence\save'),
            new MethodCallToFuncCallWithObjectAsFirstParameter('_refresh', 'Zenstruck\Foundry\Persistence\refresh'),
            new MethodCallToFuncCallWithObjectAsFirstParameter('_delete', 'Zenstruck\Foundry\Persistence\delete'),
            new MethodCallToFuncCallWithObjectAsFirstParameter(
                '_assertPersisted', 'Zenstruck\Foundry\Persistence\assert_persisted'
            ),
            new MethodCallToFuncCallWithObjectAsFirstParameter(
                '_assertNotPersisted', 'Zenstruck\Foundry\Persistence\assert_not_persisted'
            ),
        ]
    );

    $rectorConfig->ruleWithConfiguration(
        RemoveMethodCallRector::class,
        [
            new RemoveMethodCall('_enableAutoRefresh'),
            new RemoveMethodCall('_disableAutoRefresh'),
        ]
    );

    $rectorConfig->ruleWithConfiguration(
        RemoveFunctionCallRector::class,
        [
            new RemoveFunctionCall('Zenstruck\Foundry\Persistence\proxy'),
            new RemoveFunctionCall('Zenstruck\Foundry\Persistence\unproxy'),
        ]
    );

    $rectorConfig->rules([RemoveWithoutAutorefreshCallRector::class]);
};
