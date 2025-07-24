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
use Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWithObjectAsFirstParameter\MethodCallToFuncCallWithObjectAsFirstParameter;
use Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWithObjectAsFirstParameter\MethodCallToFuncCallWithObjectAsFirstParameterRector;

return static function(RectorConfig $rectorConfig): void {

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
};
