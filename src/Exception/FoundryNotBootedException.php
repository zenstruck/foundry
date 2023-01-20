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
 * @internal
 */
final class FoundryNotBootedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Foundry is not yet booted. Using in a test: is your Test case using the Factories trait? Using in a fixture: is ZenstruckFoundryBundle enabled for this environment?'
        );
    }
}
