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

namespace Zenstruck\Foundry\Exception;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class FactoriesTraitNotUsed extends \LogicException
{
    /**
     * @param class-string<KernelTestCase> $class
     */
    private function __construct(string $class)
    {
        parent::__construct(
            \sprintf('You must use the trait "%s" in "%s" in order to use Foundry.', Factories::class, $class)
        );
    }

    public static function throwIfComingFromKernelTestCaseWithoutFactoriesTrait(): void
    {
        $backTrace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS); // @phpstan-ignore ekinoBannedCode.function

        $testClassesExtendingKernelTestCase = \array_column(
            \array_filter(
                $backTrace,
                static fn(array $trace): bool => '->' === ($trace['type'] ?? null)
                    && isset($trace['class'])
                    && KernelTestCase::class !== $trace['class']
                    && \is_a($trace['class'], KernelTestCase::class, allow_string: true)
            ),
            'class'
        );

        if (!$testClassesExtendingKernelTestCase) {
            // no KernelTestCase found in backtrace, so nothing to check
            return;
        }

        self::throwIfClassDoesNotHaveFactoriesTrait(...$testClassesExtendingKernelTestCase);
    }

    /**
     * @param class-string<KernelTestCase> $classes
     */
    public static function throwIfClassDoesNotHaveFactoriesTrait(string ...$classes): void
    {
        if (array_any($classes, static fn(string $class): bool => (new \ReflectionClass($class))->hasMethod('_beforeHook'))) {
            // at least one KernelTestCase class in the backtrace uses Factories trait, nothing to do
            return;
        }

        // throw new self($class);

        trigger_deprecation(
            'zenstruck/foundry',
            '2.4',
            'In order to use Foundry correctly, you must use the trait "%s" in your "%s" tests. This will throw an exception in 3.0.',
            Factories::class,
            $classes[\array_key_last($classes)]
        );
    }
}
