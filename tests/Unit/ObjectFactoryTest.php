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
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object2Factory;
use Zenstruck\Foundry\Tests\Fixture\Object1;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\get;
use function Zenstruck\Foundry\object;
use function Zenstruck\Foundry\set;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type Sequence from Factory
 */
final class ObjectFactoryTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    public function defaults(): void
    {
        $object = Object1Factory::createOne();

        $this->assertSame('value1-constructor', $object->getProp1());
        $this->assertSame('default-constructor', $object->getProp2());
        $this->assertNull($object->getProp3());
    }

    /**
     * @test
     */
    public function named_constructor_defaults(): void
    {
        $object = Object1Factory::new()->instantiateWith(Instantiator::namedConstructor('factory'))->create();

        $this->assertSame('value1-named-constructor', $object->getProp1());
        $this->assertSame('default-named-constructor', $object->getProp2());
        $this->assertNull($object->getProp3());
    }

    /**
     * @test
     */
    public function default_instantiator_and_hydrator(): void
    {
        $object = Object1Factory::createOne([
            'prop1' => 'override1',
            'prop2' => 'override2',
            'prop3' => 'override3',
        ]);

        $this->assertSame('override1-constructor', $object->getProp1());
        $this->assertSame('override2-constructor', $object->getProp2());
        $this->assertSame('override3-setter', $object->getProp3());
    }

    /**
     * @test
     */
    public function without_constructor_instantiator(): void
    {
        $object = Object1Factory::new()->instantiateWith(Instantiator::withoutConstructor())->create([
            'prop1' => 'override1',
            'prop2' => 'override2',
            'prop3' => 'override3',
        ]);

        $this->assertSame('override1-setter', $object->getProp1());
        $this->assertSame('override2-setter', $object->getProp2());
        $this->assertSame('override3-setter', $object->getProp3());
    }

    /**
     * @test
     */
    public function with_closure_factory_constructor(): void
    {
        $object = Object1Factory::new()
            ->instantiateWith(Instantiator::use(fn(string $prop1) => new Object1($prop1)))
            ->create([
                'prop1' => 'override1',
                'prop2' => 'override2',
                'prop3' => 'override3',
            ])
        ;

        $this->assertSame('override1-constructor', $object->getProp1());
        $this->assertSame('override2-setter', $object->getProp2());
        $this->assertSame('override3-setter', $object->getProp3());
    }

    /**
     * @test
     */
    public function with_method_factory_constructor(): void
    {
        $object = Object1Factory::new()
            ->instantiateWith(Instantiator::use(Object1::factory(...)))
            ->create([
                'prop1' => 'override1',
                'prop2' => 'override2',
                'prop3' => 'override3',
            ])
        ;

        $this->assertSame('override1-named-constructor', $object->getProp1());
        $this->assertSame('override2-named-constructor', $object->getProp2());
        $this->assertSame('override3-setter', $object->getProp3());
    }

    /**
     * @test
     */
    public function with_named_constructor_instantiator(): void
    {
        $object = Object1Factory::new()->instantiateWith(Instantiator::namedConstructor('factory'))->create([
            'prop1' => 'override1',
            'prop2' => 'override2',
            'prop3' => 'override3',
        ]);

        $this->assertSame('override1-named-constructor', $object->getProp1());
        $this->assertSame('override2-named-constructor', $object->getProp2());
        $this->assertSame('override3-setter', $object->getProp3());
    }

    /**
     * @test
     */
    public function with_extra_and_force_mode_without_constructor(): void
    {
        $object = Object1Factory::new()
            ->instantiateWith(Instantiator::withoutConstructor()->allowExtra()->alwaysForce())
            ->create([
                'prop1' => 'override1',
                'prop2' => 'override2',
                'prop3' => 'override3',
                'extra' => 'value',
            ])
        ;

        $this->assertSame('override1', $object->getProp1());
        $this->assertSame('override2', $object->getProp2());
        $this->assertSame('override3', $object->getProp3());
    }

    /**
     * @test
     */
    public function with_configured_hydrator(): void
    {
        $object = Object1Factory::new()
            ->instantiateWith(Instantiator::withoutConstructor()->allowExtra('extra')->alwaysForce('prop2'))
            ->create([
                'prop1' => 'override1',
                'prop2' => 'override2',
                'prop3' => 'override3',
                'extra' => 'value',
            ])
        ;

        $this->assertSame('override1-setter', $object->getProp1());
        $this->assertSame('override2', $object->getProp2());
        $this->assertSame('override3-setter', $object->getProp3());
    }

    /**
     * @test
     */
    public function with_hydration_disabled(): void
    {
        $object = Object1Factory::new()
            ->instantiateWith(Instantiator::withConstructor()->disableHydration())
            ->create([
                'prop1' => 'override1',
                'prop2' => 'override2',
                'prop3' => 'override3',
                'extra' => 'value',
            ])
        ;

        $this->assertSame('override1-constructor', $object->getProp1());
        $this->assertSame('override2-constructor', $object->getProp2());
        $this->assertNull($object->getProp3());
    }

    /**
     * @test
     */
    public function with_custom_instantiator_callable(): void
    {
        $object = Object1Factory::new()
            ->instantiateWith(fn() => new Object1('custom'))
            ->create([
                'prop1' => 'override1',
                'prop2' => 'override2',
                'prop3' => 'override3',
                'extra' => 'value',
            ])
        ;

        $this->assertSame('custom-constructor', $object->getProp1());
        $this->assertSame('default-constructor', $object->getProp2());
        $this->assertNull($object->getProp3());
    }

    /**
     * @test
     */
    public function before_instantiate_hook(): void
    {
        $object = Object1Factory::new()
            ->beforeInstantiate(function(array $parameters, string $class) {
                $this->assertSame(['prop1' => 'value1'], $parameters);
                $this->assertSame(Object1::class, $class);

                return [
                    'prop1' => 'custom1',
                    'prop2' => 'custom2',
                    'prop3' => 'custom3',
                ];
            })
            ->create()
        ;

        $this->assertSame('custom1-constructor', $object->getProp1());
        $this->assertSame('custom2-constructor', $object->getProp2());
        $this->assertSame('custom3-setter', $object->getProp3());
    }

    /**
     * @test
     */
    public function after_instantiate_hook(): void
    {
        $object = Object1Factory::new()
            ->afterInstantiate(function(Object1 $object, array $parameters) {
                $this->assertSame(['prop1' => 'value1'], $parameters);

                $object->setProp3('custom3');
            })
            ->create()
        ;

        $this->assertSame('value1-constructor', $object->getProp1());
        $this->assertSame('default-constructor', $object->getProp2());
        $this->assertSame('custom3-setter', $object->getProp3());
    }

    /**
     * @test
     */
    public function create_anonymous_factory(): void
    {
        $object = factory(Object1::class, ['prop1' => 'value1'])->create(['prop2' => 'value2']);

        $this->assertSame('value1-constructor', $object->getProp1());
        $this->assertSame('value2-constructor', $object->getProp2());
        $this->assertNull($object->getProp3());

        $object = factory(Object1::class, ['prop1' => 'value1'])->create(['prop2' => 'value2']);

        $this->assertSame('value1-constructor', $object->getProp1());
        $this->assertSame('value2-constructor', $object->getProp2());
        $this->assertNull($object->getProp3());

        $object = object(Object1::class, ['prop1' => 'value1', 'prop2' => 'value2']);

        $this->assertSame('value1-constructor', $object->getProp1());
        $this->assertSame('value2-constructor', $object->getProp2());
        $this->assertNull($object->getProp3());
    }

    /**
     * @test
     */
    public function object_factories_are_converted(): void
    {
        $object = Object2Factory::createOne();

        $this->assertSame('value1-constructor', $object->object->getProp1());
    }

    /**
     * @test
     */
    public function can_create_many(): void
    {
        $objects = Object1Factory::createMany(3, fn(int $i) => ['prop1' => "value{$i}"]);

        $this->assertCount(3, $objects);
        $this->assertSame('value1-constructor', $objects[0]->getProp1());
        $this->assertSame('value2-constructor', $objects[1]->getProp1());
        $this->assertSame('value3-constructor', $objects[2]->getProp1());

        $objects = Object1Factory::new(fn(int $i) => ['prop1' => "value{$i}"])->many(3)->create();

        $this->assertCount(3, $objects);
        $this->assertSame('value1-constructor', $objects[0]->getProp1());
        $this->assertSame('value2-constructor', $objects[1]->getProp1());
        $this->assertSame('value3-constructor', $objects[2]->getProp1());
    }

    /**
     * @test
     */
    public function set_and_get_functions(): void
    {
        $object = new Object1('value');

        $this->assertSame('value-constructor', get($object, 'prop1'));

        set($object, 'prop1', 'new-value');

        $this->assertSame('new-value', get($object, 'prop1'));
    }

    /**
     * @dataProvider sequenceDataProvider
     *
     * @param Sequence $sequence
     *
     * @test
     */
    public function can_create_sequence(iterable|callable $sequence): void
    {
        self::assertEquals(
            [
                new Object1('foo1', 'bar1'),
                new Object1('foo2', 'bar2'),
            ],
            Object1Factory::createSequence($sequence),
        );
    }

    /**
     * @return iterable<string, array{Sequence}>
     */
    public static function sequenceDataProvider(): iterable
    {
        yield 'sequence as array' => [
            [
                [
                    'prop1' => 'foo1',
                    'prop2' => 'bar1',
                ],
                [
                    'prop1' => 'foo2',
                    'prop2' => 'bar2',
                ],
            ],
        ];

        yield 'sequence as iterable which returns array' => [
            static fn() => array_map(
                static fn(int $i) => ['prop1' => "foo{$i}", 'prop2' => "bar{$i}"],
                range(1, 2)
            )
        ];

        yield 'sequence as iterable which returns generator' => [
            static function () {
                foreach (range(1, 2) as $i) {
                    yield [
                        'prop1' => "foo{$i}",
                        'prop2' => "bar{$i}",
                    ];
                }
            }
        ];
    }

    /**
     * @test
     */
    public function as_data_provider(): void
    {
        $this->markTestIncomplete();
    }
}
