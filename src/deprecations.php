<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Zenstruck\Foundry\Persistence\RepositoryDecorator;
use Zenstruck\Foundry\RepositoryProxy;

\class_alias(\Zenstruck\Foundry\Persistence\RepositoryAssertions::class, \Zenstruck\Foundry\RepositoryAssertions::class);
\class_alias(RepositoryDecorator::class, RepositoryProxy::class);
