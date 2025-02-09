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
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\ArrayFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ArrayFactoryTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function can_create_with_defaults(): void
    {
        $this->assertSame(
            [
                'router' => false,
                'default1' => 'default value 1',
                'default2' => 'default value 2',
                'fake' => 'value',
            ],
            ArrayFactory::createOne()
        );
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_with_overrides(): void
    {
        $this->assertSame(
            [
                'router' => false,
                'default1' => 'default value 1',
                'default2' => 'override value 2',
                'fake' => 'value',
                'foo' => 'baz',
            ],
            ArrayFactory::new(['foo' => 'bar'])
                ->with(fn() => ['foo' => LazyValue::new(fn() => 'baz')])
                ->create(['default2' => 'override value 2'])
        );
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_many(): void
    {
        $this->assertCount(2, ArrayFactory::createMany(2));
        $this->assertSame(
            [
                'router' => false,
                'default1' => 'default value 1',
                'default2' => 'default value 2',
                'fake' => 'value',
            ],
            ArrayFactory::createMany(1)[0],
        );
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_range(): void
    {
        $range = ArrayFactory::createRange(2, 4);

        $this->assertGreaterThanOrEqual(2, \count($range));
        $this->assertLessThanOrEqual(4, \count($range));
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_sequence(): void
    {
        $sequence = ArrayFactory::createSequence([
            ['foo' => 'bar'],
            ['foo' => 'baz'],
        ]);

        $this->assertCount(2, $sequence);
        $this->assertSame('bar', $sequence[0]['foo']);
        $this->assertSame('baz', $sequence[1]['foo']);
    }
}
