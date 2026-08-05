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

use Behat\Behat\Context\Context;

/**
 * Marker interface for contexts providing Foundry steps.
 *
 * Implement it together with the step traits (CreationSteps, AssertionSteps,
 * PlaceholderTransforms) to build a custom context, e.g. to replace the wording of
 * some built-in steps. The built-in steps normalize their property tables themselves
 * (object references, dates, enums) through the TableParametersNormalizer service.
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @experimental
 */
interface FoundryContextInterface extends Context
{
}
