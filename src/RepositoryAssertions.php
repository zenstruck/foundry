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

trigger_deprecation('zenstruck\foundry', '1.37.0', sprintf('Class "%s" is deprecated and will be removed in version 2.0. Use "%s" instead.', RepositoryAssertions::class, NewRepositoryAssertions::class));

if (false) {
    /**
     * @deprecated
     */
    final class RepositoryAssertions
    {
    }
}
