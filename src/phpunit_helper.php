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

namespace Zenstruck\Foundry;

/**
 * When using ResetDatabase trait, we're booting the kernel,
 * which registers the Symfony's error handler too soon.
 * It is then impossible for PHPUnit to handle deprecations.
 *
 * This method tries to mitigate this problem by restoring the error handler.
 *
 * @see https://github.com/symfony/symfony/issues/53812
 *
 * @internal
 */
function restorePhpUnitErrorHandler(): void
{
    while (true) {
        $previousHandler = \set_error_handler(static fn() => null); // @phpstan-ignore-line
        \restore_error_handler();
        $isPhpUnitErrorHandler = $previousHandler instanceof \PHPUnit\Runner\ErrorHandler; // @phpstan-ignore-line
        if (null === $previousHandler || $isPhpUnitErrorHandler) {
            break;
        }
        \restore_error_handler();
    }
}
