<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;


use Zenstruck\Foundry\Object\Instantiator as NewInstatiator;

if (!class_exists(NewInstatiator::class, false)) {
    trigger_deprecation(
        'zenstruck\foundry',
        '1.37.0',
        'Class "%s" is deprecated and will be removed in version 2.0. Use "%s" instead.',
        Instantiator::class,
        NewInstatiator::class
    );
}

\class_alias(NewInstatiator::class, Instantiator::class);

if (false) {
    /**
     * @deprecated
     */
    final class Instantiator extends NewInstatiator
    {
    }
}
