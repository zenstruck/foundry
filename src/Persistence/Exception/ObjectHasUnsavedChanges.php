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

namespace Zenstruck\Foundry\Persistence\Exception;

final class ObjectHasUnsavedChanges extends RefreshObjectFailed
{
    public function __construct(string $objectClass)
    {
        parent::__construct(
            "Cannot auto refresh \"{$objectClass}\" as there are unsaved changes. Be sure to call ->_save() or disable auto refreshing (see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#auto-refresh for details)."
        );
    }
}
