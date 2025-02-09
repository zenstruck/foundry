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
    /**
     * @test
     */
    #[Test]
    public function lazy(): void
    {
        $value = lazy(fn() => new \stdClass());

        $this->assertNotSame($value(), $value());
    }

    /**
     * @test
     */
    #[Test]
    public function memoize(): void
    {
        $value = memoize(fn() => new \stdClass());

        $this->assertSame($value(), $value());
    }

    /**
     * @test
     */
    #[Test]
    public function can_handle_nested_lazy_values(): void
    {
        $value = LazyValue::new(LazyValue::new(LazyValue::new(fn() => LazyValue::new(fn() => 'foo'))));

        $this->assertSame('foo', $value());
    }

    /**
     * @test
     */
    #[Test]
    public function can_handle_array_with_lazy_values(): void
    {
        $value = LazyValue::new(fn() => [
            5,
            LazyValue::new(fn() => 'foo'),
            6,
            'foo' => [
                'bar' => 7,
                'baz' => LazyValue::new(fn() => 'foo'),
            ],
            [8, LazyValue::new(fn() => 'foo')],
        ]);

        $this->assertSame([5, 'foo', 6, 'foo' => ['bar' => 7, 'baz' => 'foo'], [8, 'foo']], $value());
    }
}
