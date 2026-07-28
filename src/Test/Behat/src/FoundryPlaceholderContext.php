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
 * Context providing only the built-in transformations resolving id placeholders
 * (<foundry:lastId(...)> and <foundry:id(...)>) in step arguments.
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @experimental
 */
final class FoundryPlaceholderContext implements FoundryContextInterface
{
    use PlaceholderTransforms;
}
