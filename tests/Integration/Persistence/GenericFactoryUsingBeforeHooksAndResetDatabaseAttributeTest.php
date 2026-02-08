<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Integration\Persistence;

use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericFactoryUsingBeforeHooksTestCase;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ResetDatabase]
final class GenericFactoryUsingBeforeHooksAndResetDatabaseAttributeTest extends GenericFactoryUsingBeforeHooksTestCase
{
}
