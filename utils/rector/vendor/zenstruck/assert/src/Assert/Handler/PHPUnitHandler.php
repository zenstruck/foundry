<?php

/*
 * This file is part of the zenstruck/assert package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Assert\Handler;

use PHPUnit\Framework\Assert as PHPUnit;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;
use SebastianBergmann\Exporter\Exporter;
use Zenstruck\Assert\AssertionFailed;
use Zenstruck\Assert\Handler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class PHPUnitHandler implements Handler
{
    public function onSuccess(): void
    {
        // trigger a successful PHPUnit assertion to avoid "risky" tests
        PHPUnit::assertTrue(true);
    }

    public function onFailure(AssertionFailed $exception): void
    {
        PHPUnit::fail(self::failureMessage($exception));
    }

    public static function isVerbose(): bool
    {
        return \in_array('--verbose', $_SERVER['argv'], true) || \in_array('-v', $_SERVER['argv'], true);
    }

    public static function isSupported(): bool
    {
        return \class_exists(PHPUnit::class);
    }

    private static function failureMessage(AssertionFailed $exception): string
    {
        $context = $exception->context();
        $message = $exception->getMessage().self::addComparison($context);

        if (!$context || !self::isVerbose()) {
            return $message;
        }

        // don't show meta-context in context dump
        unset($context['compare_expected'], $context['compare_actual']);

        $message .= "\n\nFailure Context:\n\n";
        $exporter = new Exporter();

        foreach ($context as $name => $value) {
            $exported = $exporter->export($value);

            if (\mb_strlen($exported) > 5000) {
                // prevent ridiculously long objects
                $exported = $exporter->shortenedExport($value);
            }

            $message .= \sprintf("[%s]\n%s\n\n", $name, $exported);
        }

        return $message;
    }

    private static function addComparison(array $context): string
    {
        $expected = $context['compare_expected'] ?? null;
        $actual = $context['compare_actual'] ?? null;

        try {
            ComparatorFactory::getInstance()
                ->getComparatorFor($expected, $actual)
                ->assertEquals($expected, $actual)
            ;
        } catch (ComparisonFailure $e) {
            return $e->getDiff();
        }

        return '';
    }
}
