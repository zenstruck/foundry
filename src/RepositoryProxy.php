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
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

if (!class_exists(RepositoryDecorator::class, false)) {
    trigger_deprecation(
        'zenstruck\foundry',
        '1.37.0',
        'Class "%s" is deprecated and will be removed in version 2.0. Use "%s" instead.',
        RepositoryProxy::class,
        RepositoryDecorator::class
    );
}

\class_alias(RepositoryDecorator::class, RepositoryProxy::class);

if (false) {
    /**
     * @mixin EntityRepository<TProxiedObject>
     * @template TProxiedObject of object
     *
     * @deprecated
     *
     * @author Kevin Bond <kevinbond@gmail.com>
     */
    final class RepositoryProxy extends RepositoryDecorator
    {
    }
}
