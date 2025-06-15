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
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWIthObjectASFirstParameter\MethodCallToFuncCallWithObjectAsFirstParameter;
use Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWIthObjectASFirstParameter\MethodCallToFuncCallWIthObjectAsFirstParameterRector;

return static function(RectorConfig $rectorConfig): void {
    if (\PHP_VERSION_ID < 80400) {
        throw new \LogicException('Cannot use Foundry rector suite with PHP < 8.4');
    }

    $rectorConfig->ruleWithConfiguration(
        MethodCallToFuncCallWIthObjectAsFirstParameterRector::class,
        [
            new MethodCallToFuncCallWithObjectAsFirstParameter(Proxy::class, '_get', 'Zenstruck\Foundry\get'),
            new MethodCallToFuncCallWithObjectAsFirstParameter(Proxy::class, '_set', 'Zenstruck\Foundry\set'),

            new MethodCallToFuncCallWithObjectAsFirstParameter(Proxy::class, '_save', 'Zenstruck\Foundry\Persistence\save'),
            new MethodCallToFuncCallWithObjectAsFirstParameter(Proxy::class, '_refresh', 'Zenstruck\Foundry\Persistence\refresh'),
            new MethodCallToFuncCallWithObjectAsFirstParameter(Proxy::class, '_delete', 'Zenstruck\Foundry\Persistence\delete'),
            new MethodCallToFuncCallWithObjectAsFirstParameter(Proxy::class, '_assertPersisted', 'Zenstruck\Foundry\Persistence\assert_persisted'),
            new MethodCallToFuncCallWithObjectAsFirstParameter(Proxy::class, '_assertNotPersisted', 'Zenstruck\Foundry\Persistence\assert_not_persisted'),
        ]
    );
};
