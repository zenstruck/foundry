<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Test;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class KernelHelper
{
    /**
     * When using ResetDatabase or Factories traits, we're booting the kernel,
     * which registers the Symfony's error handler too soon.
     * It is then impossible for PHPUnit to handle deprecations.
     *
     * This method tries to mitigate this problem by restoring the error handler.
     *
     * @see https://github.com/symfony/symfony/issues/53812
     */
    public static function shutdownKernel(KernelInterface $kernel): void
    {
        $kernel->shutdown();

        while (true) {
            $previousHandler = set_error_handler(static fn() => null);
            restore_error_handler();
            $isPhpUnitErrorHandler = ($previousHandler instanceof \PHPUnit\Runner\ErrorHandler);
            if ($previousHandler === null || $isPhpUnitErrorHandler) {
                break;
            }
            restore_error_handler();
        }
    }
}
