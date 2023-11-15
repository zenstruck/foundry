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

use Zenstruck\Assert\Type;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TypeAssertion extends EvaluableAssertion
{
    /** @var mixed */
    private $value;

    /** @var Type */
    private $expected;

    /**
     * @param mixed       $value
     * @param string|null $message Available context: {value}, {expected}, {actual}
     */
    public function __construct($value, Type $expected, ?string $message = null, array $context = [])
    {
        $this->value = $value;
        $this->expected = $expected;

        parent::__construct($message, $context);
    }

    protected function evaluate(): bool
    {
        return ($this->expected)($this->value);
    }

    protected function defaultFailureMessage(): string
    {
        return 'Expected "{value}" to be of type {expected} but is {actual}.';
    }

    protected function defaultNotFailureMessage(): string
    {
        return 'Expected "{value}" to NOT be of type {expected}.';
    }

    protected function defaultContext(): array
    {
        return [
            'value' => $this->value,
            'expected' => (string) $this->expected,
            'actual' => \get_debug_type($this->value),
        ];
    }
}
