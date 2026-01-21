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

final class FoundrySetList
{
    /**
     * @deprecated use FoundrySetList::FOUNDRY_2_7
     * @var string
     */
    public const REMOVE_PROXIES = self::FOUNDRY_2_7;

    /** @var string */
    public const FOUNDRY_2_7 = __DIR__.'/../config/foundry-2.7.php';

    /** @var string */
    public const FOUNDRY_2_9 = __DIR__.'/../config/foundry-2.9.php';
}
