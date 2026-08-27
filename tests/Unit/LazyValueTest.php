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

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\SimpleObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\SimpleObject;

use function Zenstruck\Foundry\lazy;
use function Zenstruck\Foundry\memoize;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyValueTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function lazy(): void
    {
        $value = lazy(static fn() => new \stdClass());

        $this->assertNotSame($value(), $value());
    }

    /**
     * @test
     */
    #[Test]
    public function memoize(): void
    {
        $value = memoize(static fn() => new \stdClass());

        $this->assertSame($value(), $value());
    }

    /**
     * @test
     */
    #[Test]
    public function can_handle_nested_lazy_values(): void
    {
        $value = LazyValue::new(LazyValue::new(LazyValue::new(static fn() => LazyValue::new(static fn() => 'foo'))));

        $this->assertSame('foo', $value());
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    #[Test]
    public function factory_memoize_returns_the_same_object(): void
    {
        $value = SimpleObjectFactory::new()->memoize();

        $object = $value();

        $this->assertInstanceOf(SimpleObject::class, $object);
        $this->assertSame($object, $value());
    }

    /**
     * @test
     */
    #[Test]
    public function factory_collection_memoize_returns_the_same_objects(): void
    {
        $value = SimpleObjectFactory::new()->many(2)->memoize();

        $objects = $value();

        $this->assertCount(2, $objects);
        $this->assertContainsOnlyInstancesOf(SimpleObject::class, $objects);
        $this->assertSame($objects, $value());
    }

    /**
     * @test
     *
     * @group legacy
     * @requires PHPUnit >=11.0.0
     */
    #[Test]
    #[IgnoreDeprecations]
    #[RequiresPhpunit('>=11.0.0')]
    public function memoizing_a_factory_creates_a_new_object_on_each_use_and_is_deprecated(): void
    {
        $this->expectUserDeprecationMessageMatches('/Passing a factory to memoize\(\) is deprecated/');

        $value = memoize(static fn() => SimpleObjectFactory::new());

        $this->assertSame($value(), $value());
    }
}
