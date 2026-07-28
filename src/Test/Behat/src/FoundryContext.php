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
 * All-in-one context providing every built-in Foundry step and transformation.
 *
 * To use a subset of the steps, use FoundryCreationContext, FoundryAssertionContext
 * and/or FoundryPlaceholderContext instead. To replace the wording of some steps,
 * compose your own context from the step traits (see the documentation).
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @experimental
 */
final class FoundryContext implements FoundryContextInterface
{
    use AssertionSteps;
    use CreationSteps;
    use PlaceholderTransforms;
}
