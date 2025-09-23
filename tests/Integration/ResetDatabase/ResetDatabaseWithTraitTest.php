<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ResetDatabaseWithTraitTest extends KernelTestCase
{
    use ResetDatabase, ResetDatabaseTestsTrait;
}
