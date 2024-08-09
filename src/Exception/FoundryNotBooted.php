<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FoundryNotBooted extends \LogicException
{
    public function __construct()
    {
        parent::__construct('Foundry is not yet booted. Ensure ZenstruckFoundryBundle is enabled. If in a test, ensure your TestCase has the Factories trait.');
    }
}
