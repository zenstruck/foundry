<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Persistence;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Persistence\ProxyGenerator;
use Zenstruck\Foundry\Test\Factories;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @group legacy
 */
#[IgnoreDeprecations]
final class ProxyGeneratorTest extends TestCase
{
    use Factories;

    /**
     * @test
     * @dataProvider classWithUnserializeMagicMethodProvider
     */
    #[Test]
    #[DataProvider('classWithUnserializeMagicMethodProvider')]
    public function it_can_generate_proxy_for_class_with_unserialize_magic_method(object $obj): void
    {
        $proxyfiedObj = ProxyGenerator::wrap($obj);
        self::assertEquals(\unserialize(\serialize($proxyfiedObj))->_real(), $proxyfiedObj->_real());
    }

    public static function classWithUnserializeMagicMethodProvider(): iterable
    {
        yield 'not type hinted __unserialize method' => [new ClassWithNoTypeHintInUnserialize()];
        yield 'type hinted __unserialize method' => [new ClassWithTypeHintedUnserialize()];
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_generate_proxy_for_class_with_self_return_type(): void
    {
        $proxyfiedObj = ProxyGenerator::wrap($obj = new ClassWithSelfReturnType());
        self::assertSame($obj, $proxyfiedObj->returnsSelf()->_real()); // @phpstan-ignore method.notFound
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_generate_proxy_for_class_with_method_with_nullable_return_type(): void
    {
        $proxyfiedObj = ProxyGenerator::wrap(new ClassWithNullableReturnType());
        self::assertNull($proxyfiedObj->returnsNullable(null));
        self::assertSame(1, $proxyfiedObj->returnsNullable(1));
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_generate_proxy_for_class_with_method_with_no_return_type(): void
    {
        $proxyfiedObj = ProxyGenerator::wrap(new ClassWithoutReturnType());
        self::assertSame(1, $proxyfiedObj->returnsSeomthing());
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_generate_proxy_for_class_with_method_with_union_return_type(): void
    {
        $proxyfiedObj = ProxyGenerator::wrap(new ClassWithUnionReturnType());
        self::assertSame(1, $proxyfiedObj->returnsUnionType());
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_generate_proxy_for_class_with_method_with_intersection_return_type(): void
    {
        $proxyfiedObj = ProxyGenerator::wrap(new ClassWithInterSectionReturnType());
        self::assertInstanceOf(One::class, $proxyfiedObj->returnsIntersectionType());
        self::assertInstanceOf(Two::class, $proxyfiedObj->returnsIntersectionType());
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_generate_proxy_for_class_with_method_with_attribute_added_by_proxy_helper(): void
    {
        $proxyfiedObj = ProxyGenerator::wrap(new ClassWithAttributeAddedByProxyHelper());
        self::assertSame(1, $proxyfiedObj->jsonSerialize());
    }
}

class ClassWithNoTypeHintInUnserialize
{
    public function __unserialize($array) // @phpstan-ignore missingType.parameter
    {
    }
}

class ClassWithTypeHintedUnserialize
{
    public function __unserialize(array $array)
    {
    }
}

class ClassWithNullableReturnType
{
    public function returnsNullable(?int $int): ?int
    {
        return $int;
    }
}

class ClassWithoutReturnType
{
    public function returnsSeomthing() // @phpstan-ignore missingType.return
    {
        return 1;
    }
}

class ClassWithSelfReturnType
{
    public function returnsSelf(): self
    {
        return $this;
    }
}

class ClassWithUnionReturnType
{
    public function returnsUnionType(): int|string|\DateTimeImmutable
    {
        return 1;
    }
}

interface One
{
}
interface Two
{
}

class ClassWithInterSectionReturnType
{
    public function returnsIntersectionType(): One&Two
    {
        return new class implements One, Two {};
    }
}

class ClassWithAttributeAddedByProxyHelper implements \JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return 1;
    }
}
