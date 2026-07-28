<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat;

/**
 * Context providing only the built-in "Then" steps asserting on Foundry objects
 * and on the database.
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @experimental
 */
final class FoundryAssertionContext implements FoundryContextInterface
{
    use AssertionSteps;
}
