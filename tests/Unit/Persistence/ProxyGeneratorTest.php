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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Persistence\ProxyGenerator;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ProxyGeneratorTest extends TestCase
{
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
