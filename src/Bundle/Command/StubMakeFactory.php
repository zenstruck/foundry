<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Bundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:factory')]
final class StubMakeFactory extends StubCommand
{
}
