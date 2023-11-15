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

use Zenstruck\Assert\AssertionFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class EvaluableAssertion implements Negatable
{
    /** @var string|null */
    private $message;

    /** @var array */
    private $context;

    public function __construct(?string $message = null, array $context = [])
    {
        $this->message = $message;
        $this->context = $context;
    }

    final public function __invoke(): void
    {
        if (!$this->evaluate()) {
            throw $this->createFailure($this->defaultFailureMessage());
        }
    }

    final public function notFailure(): AssertionFailed
    {
        return $this->createFailure($this->defaultNotFailureMessage());
    }

    /**
     * @return bool "true" for "success"
     */
    abstract protected function evaluate(): bool;

    abstract protected function defaultFailureMessage(): string;

    abstract protected function defaultNotFailureMessage(): string;

    /**
     * @return array The context to use for creating failure messages
     */
    abstract protected function defaultContext(): array;

    private function createFailure(string $defaultMessage): AssertionFailed
    {
        return new AssertionFailed(
            $this->message ?? $defaultMessage,
            \array_merge($this->defaultContext(), $this->context)
        );
    }
}
