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

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

if (!\class_exists(ProxyRepositoryDecorator::class, false)) {
    trigger_deprecation(
        'zenstruck\foundry',
        '1.38.0',
        'Class "%s" is deprecated and will be removed in version 2.0. Use "%s" instead.',
        RepositoryProxy::class,
        ProxyRepositoryDecorator::class,
    );
}

\class_alias(ProxyRepositoryDecorator::class, RepositoryProxy::class);

if (false) {
    /**
     * @mixin EntityRepository<TProxiedObject>
     * @template TProxiedObject of object
     *
     * @extends ProxyRepositoryDecorator<TProxiedObject>
     *
     * @deprecated
     *
     * @author Kevin Bond <kevinbond@gmail.com>
     */
    final class RepositoryProxy extends ProxyRepositoryDecorator
    {
    }
}
