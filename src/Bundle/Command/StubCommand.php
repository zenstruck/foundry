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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class StubCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new SymfonyStyle($input, $output))
            ->error(
                \sprintf("To run \"%s\" you need the \"MakerBundle\" which is currently not installed.\n\nTry running \"composer require symfony/maker-bundle --dev\".", static::getDefaultName())
            )
        ;

        return Command::FAILURE;
    }
}
