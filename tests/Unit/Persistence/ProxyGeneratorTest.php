<?php

declare(strict_types=1);

namespace Unit\Persistence;

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
    public function it_can_generate_proxy_for_class_with_unserialize_magic_method(object $obj): void
    {
        $proxyfiedObj = ProxyGenerator::wrap($obj);
        self::assertEquals(unserialize(serialize($proxyfiedObj))->_real(), $proxyfiedObj->_real());

        // if this assertion fails, https://github.com/symfony/symfony/pull/57460 have been released
        // so, the monkey patch around contravariance problem could be removed
        self::assertFalse((new \ReflectionClass($proxyfiedObj))->hasMethod('__doUnserialize'));
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
