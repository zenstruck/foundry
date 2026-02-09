<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\LazyValue;

use function Zenstruck\Foundry\lazy;
use function Zenstruck\Foundry\memoize;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyValueTest extends TestCase
{
    #[Test]
    public function lazy(): void
    {
        $value = lazy(static fn() => new \stdClass());

        $this->assertNotSame($value(), $value());
    }

    #[Test]
    public function memoize(): void
    {
        $value = memoize(static fn() => new \stdClass());

        $this->assertSame($value(), $value());
    }

    #[Test]
    public function can_handle_nested_lazy_values(): void
    {
        $value = LazyValue::new(LazyValue::new(LazyValue::new(static fn() => LazyValue::new(static fn() => 'foo'))));

        $this->assertSame('foo', $value());
    }

    #[Test]
    public function can_handle_array_with_lazy_values(): void
    {
        $value = LazyValue::new(static fn() => [
            5,
            LazyValue::new(static fn() => 'foo'),
            6,
            'foo' => [
                'bar' => 7,
                'baz' => LazyValue::new(static fn() => 'foo'),
            ],
            [8, LazyValue::new(static fn() => 'foo')],
        ]);

        $this->assertSame([5, 'foo', 6, 'foo' => ['bar' => 7, 'baz' => 'foo'], [8, 'foo']], $value());
    }
}
