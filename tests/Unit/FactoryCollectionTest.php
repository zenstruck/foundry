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

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Test\Factories;

final class FactoryCollectionTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function throws_when_method_does_not_exist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('State method "nonExistentMethod" does not exist');

        SomeFactory::new()
            ->many(1)
            ->applyStateMethod('nonExistentMethod', fn() => []);
    }

    /**
     * @test
     */
    #[Test]
    public function throws_when_method_is_static(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is static and cannot be used as a state method');

        SomeFactory::new()
            ->many(1)
            ->applyStateMethod('class', fn() => []);
    }

    /**
     * @test
     * @testWith [[]]
     *           [["a", "b"]]
     */
    #[Test]
    #[TestWith([[]])]
    #[TestWith([['a', 'b']])]
    public function throws_when_parameter_number_do_not_match(array $parametersReturned): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid number of parameters for state method');

        SomeFactory::new()
            ->many(1)
            ->applyStateMethod('stateMethod', fn() => $parametersReturned)
            ->all()
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function throws_when_does_not_return_static(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('does not return a "%s"', SomeFactory::class));

        SomeFactory::new()
            ->many(1)
            ->applyStateMethod('notStateMethod', fn() => [])
            ->all()
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_call_state_method_without_parameter(): void
    {
        $objects = SomeFactory::new()
            ->many(2)
            ->applyStateMethod('stateMethodNoParameter')
            ->create()
        ;

        self::assertSame(42, $objects[0]->param);
        self::assertSame(42, $objects[1]->param);
    }

    /**
     * @test
     */
    #[Test]
    public function can_call_state_method_with_parameter(): void
    {
        $objects = SomeFactory::new()
            ->many(2)
            ->applyStateMethod('stateMethod', fn(int $i) => [$i])
            ->create()
        ;

        self::assertSame(1, $objects[0]->param);
        self::assertSame(2, $objects[1]->param);
    }

    /**
     * @test
     */
    #[Test]
    public function can_call_state_method_with_named_parameter(): void
    {
        $objects = SomeFactory::new()
            ->many(2)
            ->applyStateMethod('stateMethodWithTwoParameters', fn(int $i) => ['value2' => 42, 'value1' => $i])
            ->create()
        ;

        self::assertSame(41, $objects[0]->param);
        self::assertSame(40, $objects[1]->param);
    }

    /**
     * @test
     */
    #[Test]
    public function throws_when_called_with_not_existing_named_parameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter(s) "foo,bar" don\'t exist for state method');

        SomeFactory::new()
            ->many(1)
            ->applyStateMethod('stateMethodWithTwoParameters', fn(int $i) => ['foo' => 42, 'bar' => 42])
            ->create()
        ;
    }
}

/** @extends ObjectFactory<SomeObject> */
final class SomeFactory extends ObjectFactory
{
    protected function defaults(): array
    {
        return [];
    }

    public static function class(): string
    {
        return SomeObject::class;
    }

    public function stateMethod(int $value): static
    {
        return $this->with(['param' => $value]);
    }

    public function stateMethodWithTwoParameters(int $value1, int $value2): static
    {
        return $this->with(['param' => $value2 - $value1]);
    }

    public function stateMethodNoParameter(): static
    {
        return $this->with(['param' => 42]);
    }

    public function notStateMethod(): SomeObject
    {
        return new SomeObject();
    }
}

final class SomeObject
{
    public int $param = 0;
}
