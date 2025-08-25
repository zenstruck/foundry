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

final class ObjectNoLongerExist extends RefreshObjectFailed
{
    public function __construct(public readonly object $originalObject)
    {
        parent::__construct('object no longer exists...');
    }
}
