<?php

/*
 * This file is part of the zenstruck/assert package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Assert\Assertion;

use Zenstruck\Assert\AssertionFailed;
use Zenstruck\Assert\Not;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Negatable
{
    public function __invoke(): void;

    /**
     * The failure to use if assertion passed but negated {@see Not}.
     */
    public function notFailure(): AssertionFailed;
}
