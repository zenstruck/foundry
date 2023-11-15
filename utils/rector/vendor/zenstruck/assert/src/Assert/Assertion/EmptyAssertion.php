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
final class EmptyAssertion extends EvaluableAssertion
{
    /** @var mixed */
    private $actual;

    /**
     * @param mixed       $actual  Value that can be counted or evaluated as "empty"
     * @param string|null $message Available context: {actual}, {count} (if countable)
     */
    public function __construct($actual, ?string $message = null, array $context = [])
    {
        $this->actual = $actual;

        parent::__construct($message, $context);
    }

    protected function evaluate(): bool
    {
        if (null !== $count = $this->count()) {
            return 0 === $count;
        }

        return empty($this->actual);
    }

    protected function defaultFailureMessage(): string
    {
        if (null !== $this->count()) {
            return 'Expected "{actual}" to be empty but its count is {count}.';
        }

        return 'Expected "{actual}" to be empty.';
    }

    protected function defaultNotFailureMessage(): string
    {
        return 'Expected "{actual}" to not be empty.';
    }

    protected function defaultContext(): array
    {
        return ['actual' => $this->actual, 'count' => $this->count()];
    }

    private function count(): ?int
    {
        if (\is_countable($this->actual)) {
            return \count($this->actual);
        }

        if (\is_iterable($this->actual)) {
            return \iterator_count($this->actual);
        }

        return null;
    }
}
