<?php

/*
 * This file is part of the zenstruck/assert package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Assert;

use Zenstruck\Assert\Assertion\Negatable;

/**
 * Wraps a {@see Negatable} assertion and throws a {@see AssertionFailed}
 * if it passes.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Not
{
    /** @var Negatable */
    private $assertion;

    public function __construct(Negatable $assertion)
    {
        $this->assertion = $assertion;
    }

    public function __invoke(): void
    {
        try {
            ($this->assertion)();
        } catch (AssertionFailed $e) {
            return;
        }

        throw $this->assertion->notFailure();
    }

    public static function wrap(Negatable $assertion): self
    {
        return new self($assertion);
    }
}
