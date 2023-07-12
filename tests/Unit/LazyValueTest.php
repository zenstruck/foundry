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

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\LazyValue;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyValueTest extends TestCase
{
    /**
     * @test
     */
    public function executes_factory(): void
    {
        $value = LazyValue::new(fn() => 'foo');

        $this->assertSame('foo', $value());
    }

    /**
     * @test
     */
    public function can_handle_nested_lazy_values(): void
    {
        $value = LazyValue::new(LazyValue::new(LazyValue::new(fn() => LazyValue::new(fn() => 'foo'))));

        $this->assertSame('foo', $value());
    }

    /**
     * @test
     */
    public function can_handle_array_with_lazy_values(): void
    {
        $value = LazyValue::new(function() {
            return [
                5,
                LazyValue::new(fn() => 'foo'),
                6,
                'foo' => [
                    'bar' => 7,
                    'baz' => LazyValue::new(fn() => 'foo'),
                ],
                [8, LazyValue::new(fn() => 'foo')],
            ];
        });

        $this->assertSame([5, 'foo', 6, 'foo' => ['bar' => 7, 'baz' => 'foo'], [8, 'foo']], $value());
    }

    /**
     * @test
     * @group legacy
     */
    public function does_not_memoize_value_by_default(): void
    {
        $value = new LazyValue(fn() => new \stdClass());

        $this->assertNotSame($value(), $value());
    }

    /**
     * @test
     */
    public function does_not_memoize_value(): void
    {
        $value = LazyValue::new(fn() => new \stdClass());

        $this->assertNotSame($value(), $value());
    }

    /**
     * @test
     */
    public function can_handle_memoized_value(): void
    {
        $value = LazyValue::memoize(fn() => new \stdClass());

        $this->assertSame($value(), $value());
    }
}
