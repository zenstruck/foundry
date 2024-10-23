<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
function runCommand(Application $application, string $command, bool $canFail = false): void
{
    $exit = $application->run(new StringInput("$command --no-interaction"), $output = new BufferedOutput());

    if (0 !== $exit && !$canFail) {
        throw new \RuntimeException(\sprintf('Error running "%s": %s', $command, $output->fetch()));
    }
}

/**
 * @internal
 */
function application(KernelInterface $kernel): Application
{
    $application = new Application($kernel);
    $application->setAutoExit(false);

    return $application;
}
