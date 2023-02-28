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
        $value = new LazyValue(fn() => 'foo');

        $this->assertSame('foo', $value());
    }

    /**
     * @test
     */
    public function can_handle_nested_lazy_values(): void
    {
        $value = new LazyValue(new LazyValue(new LazyValue(fn() => new LazyValue(fn() => 'foo'))));

        $this->assertSame('foo', $value());
    }

    /**
     * @test
     */
    public function can_handle_array_with_lazy_values(): void
    {
        $value = new LazyValue(function() {
            return [
                5,
                new LazyValue(fn() => 'foo'),
                6,
                'foo' => [
                    'bar' => 7,
                    'baz' => new LazyValue(fn() => 'foo'),
                ],
                [8, new LazyValue(fn() => 'foo')],
            ];
        });

        $this->assertSame([5, 'foo', 6, 'foo' => ['bar' => 7, 'baz' => 'foo'], [8, 'foo']], $value());
    }
}
