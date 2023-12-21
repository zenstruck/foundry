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

use Zenstruck\Foundry\Persistence\RepositoryAssertions as NewRepositoryAssertions;

if (!class_exists(NewRepositoryAssertions::class, false)) {
    trigger_deprecation(
        'zenstruck\foundry',
        '1.37.0',
        'Class "%s" is deprecated and will be removed in version 2.0. Use "%s" instead.',
        RepositoryAssertions::class,
        NewRepositoryAssertions::class
    );
}

\class_alias(NewRepositoryAssertions::class, RepositoryAssertions::class);

if (false) {
    /**
     * @deprecated
     */
    final class RepositoryAssertions extends NewRepositoryAssertions
    {
    }
}
