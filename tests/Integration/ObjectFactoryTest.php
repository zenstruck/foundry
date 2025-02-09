<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object2Factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ObjectFactoryTest extends KernelTestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function can_create_service_factory(): void
    {
        $object = Object1Factory::createOne();

        $this->assertSame('router-constructor', $object->getProp1());
        $this->assertSame('default-constructor', $object->getProp2());
        $this->assertNull($object->getProp3());
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_non_service_factories(): void
    {
        $object = Object2Factory::createOne();

        $this->assertSame('router-constructor', $object->object->getProp1());
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_different_objects_based_on_same_factory(): void
    {
        $factory = Object1Factory::new(['prop1' => 'first object']);
        $object1 = $factory->create();
        self::assertSame('first object-constructor', $object1->getProp1());

        $object2 = $factory->create(['prop1' => 'second object']);
        self::assertSame('second object-constructor', $object2->getProp1());

        $object3 = $factory->with(['prop1' => 'third object'])->create();
        self::assertSame('third object-constructor', $object3->getProp1());
    }
}
