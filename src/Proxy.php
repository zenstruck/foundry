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

use Zenstruck\Foundry\Persistence\Proxy as ProxyBase;

trigger_deprecation('zenstruck\foundry', '1.37.0', \sprintf('Class "%s" is deprecated and will be removed in version 2.0. Use "%s" instead.', Proxy::class, ProxyBase::class));

if (false) {
    /**
     * @template TProxiedObject of object
     * @mixin TProxiedObject
     *
     * @deprecated
     *
     * @author Kevin Bond <kevinbond@gmail.com>
     */
    final class Proxy
    {
    }
}
