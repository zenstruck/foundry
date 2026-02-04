<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Behat;

use Yceruto\BehatExtension\Context\ExceptionAssertionTrait;
use Zenstruck\Foundry\Test\Behat\FoundryContext;

final class TestFoundryContext extends FoundryContext // @phpstan-ignore class.extendsFinalByPhpDoc
{
    use ExceptionAssertionTrait;
}
