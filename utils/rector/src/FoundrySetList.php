<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Utils\Rector;

use Rector\Set\Contract\SetListInterface;

final class FoundrySetList implements SetListInterface
{
    /** @var string */
    public const UP_TO_FOUNDRY_2 = __DIR__.'/../config/foundry-set.php';
}
