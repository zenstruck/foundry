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
final class ThrowsAssertion
{
    /** @var callable */
    private $during;

    /** @var string */
    private $expectedException;

    /** @var string|null */
    private $expectedMessage;

    /** @var callable */
    private $onCatch;

    /**
     * @param callable        $during            Considered a "fail" if, when invoked,
     *                                           $expectedException isn't thrown
     * @param string|callable $expectedException string: class name of the expected exception
     *                                           callable: uses the first argument's type-hint
     *                                           to determine the expected exception class. When
     *                                           exception is caught, callable is invoked with
     *                                           the caught exception
     * @param string|null     $expectedMessage   Assert the caught exception message "contains"
     *                                           this string
     */
    public function __construct(callable $during, $expectedException, ?string $expectedMessage = null)
    {
        $onCatch = static function() {};

        if (\is_callable($expectedException)) {
            $parameterRef = (new \ReflectionFunction(\Closure::fromCallable($expectedException)))->getParameters()[0] ?? null;

            if (!$parameterRef || !($type = $parameterRef->getType()) instanceof \ReflectionNamedType) {
                throw new \InvalidArgumentException('When $exception is a callback, the first parameter must be type-hinted as the expected exception.');
            }

            $onCatch = $expectedException;
            $expectedException = $type->getName();
        }

        if (!\is_string($expectedException)) {
            throw new \InvalidArgumentException(\sprintf('Expected exception must a string representation of a class or interface, "%s" given.', \get_debug_type($expectedException)));
        }

        if (!\class_exists($expectedException) && !\interface_exists($expectedException)) {
            throw new \InvalidArgumentException(\sprintf('Expected exception must be an object or interface, "%s" given.', $expectedException));
        }

        $this->during = $during;
        $this->expectedException = $expectedException;
        $this->expectedMessage = $expectedMessage;
        $this->onCatch = $onCatch;
    }

    public function __invoke(): void
    {
        try {
            ($this->during)();
        } catch (\Throwable $actual) {
            if (!$actual instanceof $this->expectedException) {
                AssertionFailed::throw(
                    'Expected "{expected_exception}" to be thrown but got "{actual_exception}".',
                    ['expected_exception' => $this->expectedException, 'actual_exception' => $actual]
                );
            }

            if ($this->expectedMessage && !\str_contains($actual->getMessage(), $this->expectedMessage)) {
                AssertionFailed::throw(
                    'Expected "{actual_exception}" message "{actual_message}" to contain "{expected_message}".',
                    [
                        'actual_exception' => $this->expectedException,
                        'actual_message' => $actual->getMessage(),
                        'expected_message' => $this->expectedMessage,
                    ]
                );
            }

            ($this->onCatch)($actual);

            return;
        }

        AssertionFailed::throw(
            'No exception thrown. Expected "{expected_exception}".',
            ['expected_exception' => $this->expectedException]
        );
    }
}
