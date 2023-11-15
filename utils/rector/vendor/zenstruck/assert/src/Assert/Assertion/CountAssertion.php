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
final class CountAssertion extends EvaluableAssertion
{
    /** @var int */
    private $expected;

    /** @var iterable|\Countable */
    private $haystack;

    /**
     * @param iterable|\Countable $haystack
     * @param string|null         $message  Available context: {expected}, {actual}, {haystack}
     */
    public function __construct(int $expected, $haystack, ?string $message = null, array $context = [])
    {
        if (!\is_countable($haystack) && !\is_iterable($haystack)) {
            throw new \InvalidArgumentException(\sprintf('$haystack must be countable or iterable, "%s" given.', \get_debug_type($haystack)));
        }

        $this->expected = $expected;
        $this->haystack = $haystack;

        parent::__construct($message, $context);
    }

    protected function evaluate(): bool
    {
        return $this->expected === $this->count();
    }

    protected function defaultFailureMessage(): string
    {
        return 'Expected the count of {haystack} to be {expected} but got {actual}.';
    }

    protected function defaultNotFailureMessage(): string
    {
        return 'Expected the count of {haystack} to not be {expected}.';
    }

    protected function defaultContext(): array
    {
        return [
            'expected' => $this->expected,
            'actual' => $this->count(),
            'haystack' => $this->haystack,
        ];
    }

    private function count(): int
    {
        return \is_countable($this->haystack) ? \count($this->haystack) : \iterator_count($this->haystack);
    }
}
