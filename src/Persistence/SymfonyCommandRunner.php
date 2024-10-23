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

namespace Zenstruck\Foundry\Persistence;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
trait SymfonyCommandRunner
{
    /**
     * @param array<string,scalar> $parameters
     */
    final protected static function runCommand(Application $application, string $command, array $parameters = [], bool $canFail = false): void
    {
        $exit = $application->run(
            new ArrayInput(\array_merge(['command' => $command], $parameters + ['--no-interaction' => true])),
            $output = new BufferedOutput()
        );

        if (0 !== $exit && !$canFail) {
            throw new \RuntimeException(\sprintf('Error running "%s": %s', $command, $output->fetch()));
        }
    }

    final protected static function application(KernelInterface $kernel): Application
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        return $application;
    }
}
