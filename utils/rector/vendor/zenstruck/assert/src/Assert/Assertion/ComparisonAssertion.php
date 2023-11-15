<?php

/*
 * This file is part of the zenstruck/assert package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Assert\Assertion;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComparisonAssertion extends EvaluableAssertion
{
    private const SAME = 'the same as';
    private const EQUAL = 'equal to';
    private const GREATER_THAN = 'greater than';
    private const GREATER_THAN_OR_EQUAL = 'greater than or equal to';
    private const LESS_THAN = 'less than';
    private const LESS_THAN_OR_EQUAL = 'less than or equal to';

    /** @var mixed */
    private $actual;

    /** @var mixed */
    private $expected;

    /** @var string */
    private $comparison;

    /**
     * @param mixed $actual
     * @param mixed $expected
     */
    private function __construct($actual, $expected, string $comparison, ?string $message = null, array $context = [])
    {
        $this->actual = $actual;
        $this->expected = $expected;
        $this->comparison = $comparison;

        parent::__construct($message, $context);
    }

    /**
     * Asserts $expected and $actual are "the same" using "===".
     *
     * @param mixed       $actual
     * @param mixed       $expected
     * @param string|null $message  Available context: {expected}, {actual}
     */
    public static function same($actual, $expected, ?string $message = null, array $context = []): self
    {
        return new self($actual, $expected, self::SAME, $message, $context);
    }

    /**
     * Asserts $expected and $actual are "equal" using "==".
     *
     * @param mixed       $actual
     * @param mixed       $expected
     * @param string|null $message  Available context: {expected}, {actual}
     */
    public static function equal($actual, $expected, ?string $message = null, array $context = []): self
    {
        return new self($actual, $expected, self::EQUAL, $message, $context);
    }

    /**
     * Asserts $expected is "greater than" $actual.
     *
     * @param mixed       $actual
     * @param mixed       $expected
     * @param string|null $message  Available context: {expected}, {actual}
     */
    public static function greaterThan($actual, $expected, ?string $message = null, array $context = []): self
    {
        return new self($actual, $expected, self::GREATER_THAN, $message, $context);
    }

    /**
     * Asserts $expected is "greater than or equal to" $actual.
     *
     * @param mixed       $actual
     * @param mixed       $expected
     * @param string|null $message  Available context: {expected}, {actual}
     */
    public static function greaterThanOrEqual($actual, $expected, ?string $message = null, array $context = []): self
    {
        return new self($actual, $expected, self::GREATER_THAN_OR_EQUAL, $message, $context);
    }

    /**
     * Asserts $expected is "less than" $actual.
     *
     * @param mixed       $actual
     * @param mixed       $expected
     * @param string|null $message  Available context: {expected}, {actual}
     */
    public static function lessThan($actual, $expected, ?string $message = null, array $context = []): self
    {
        return new self($actual, $expected, self::LESS_THAN, $message, $context);
    }

    /**
     * Asserts $expected is "less than or equal to" $actual.
     *
     * @param mixed       $actual
     * @param mixed       $expected
     * @param string|null $message  Available context: {expected}, {actual}
     */
    public static function lessThanOrEqual($actual, $expected, ?string $message = null, array $context = []): self
    {
        return new self($actual, $expected, self::LESS_THAN_OR_EQUAL, $message, $context);
    }

    protected function evaluate(): bool
    {
        switch ($this->comparison) {
            case self::SAME:
                return $this->expected === $this->actual;
            case self::EQUAL:
                return $this->expected == $this->actual;
            case self::GREATER_THAN:
                return $this->actual > $this->expected;
            case self::GREATER_THAN_OR_EQUAL:
                return $this->actual >= $this->expected;
            case self::LESS_THAN:
                return $this->actual < $this->expected;
        }

        // less than or equal
        return $this->actual <= $this->expected;
    }

    protected function defaultFailureMessage(): string
    {
        if (self::SAME === $this->comparison && \is_scalar($this->actual) && \is_scalar($this->expected) && \gettype($this->actual) !== \gettype($this->expected)) {
            // show the type difference
            return \sprintf(
                'Expected "(%s) {actual}" to be %s "(%s) {expected}".',
                \get_debug_type($this->actual),
                $this->comparison,
                \get_debug_type($this->expected)
            );
        }

        return \sprintf('Expected "{actual}" to be %s "{expected}".', $this->comparison);
    }

    protected function defaultNotFailureMessage(): string
    {
        return \sprintf('Expected "{actual}" to not be %s "{expected}".', $this->comparison);
    }

    protected function defaultContext(): array
    {
        $context = ['actual' => $this->actual, 'expected' => $this->expected];

        if (!\in_array($this->comparison, [self::SAME, self::EQUAL], true)) {
            return $context;
        }

        // SAME/EQUAL can add meta comparison context for PHPUnit handler

        if (self::SAME === $this->comparison && (\is_object($this->actual) || \is_object($this->expected))) {
            // when comparing if objects are "the same", don't add comparison
            return $context;
        }

        return \array_merge($context, [
            'compare_expected' => $this->expected,
            'compare_actual' => $this->actual,
        ]);
    }
}
