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

        foreach ($backTrace as $trace) {
            if (
                '->' === ($trace['type'] ?? null)
                && isset($trace['class'])
                && KernelTestCase::class !== $trace['class']
                && \is_a($trace['class'], KernelTestCase::class, allow_string: true)
                && !(new \ReflectionClass($trace['class']))->hasMethod('_bootFoundry')
            ) {
                self::throwIfClassDoesNotHaveFactoriesTrait($trace['class']);
            }
        }
    }

    /**
     * @param class-string<KernelTestCase> $class
     */
    public static function throwIfClassDoesNotHaveFactoriesTrait(string $class): void
    {
        if (!(new \ReflectionClass($class))->hasMethod('_bootFoundry')) {
            // throw new self($class);
            trigger_deprecation(
                'zenstruck/foundry',
                '2.4',
                'In order to use Foundry, you must use the trait "%s" in your "%s" tests. This will throw an exception in 3.0.',
                KernelTestCase::class,
                $class
            );
        }
    }
}
