<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class GenericFactoryUsingBeforeHooksAndResetDatabaseTraitTest extends GenericFactoryUsingBeforeHooksTestCase
{
    use Factories, ResetDatabase;
}
