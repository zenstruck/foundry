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

    $rectorConfig->ruleWithConfiguration(
        MethodCallToFuncCallWIthObjectAsFirstParameterRector::class,
        [
            new MethodCallToFuncCallWithObjectAsFirstParameter('_set', 'Zenstruck\Foundry\set'),
            new MethodCallToFuncCallWithObjectAsFirstParameter('_save', 'Zenstruck\Foundry\Persistence\save'),
        ]
    );
};
