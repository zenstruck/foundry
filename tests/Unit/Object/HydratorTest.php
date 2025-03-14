<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Object;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Object\Hydrator;
use Zenstruck\Foundry\Tests\Fixture\Object1;

/**
 * @author Maarten de Boer <info@maartendeboer.net>
 */
class HydratorTest extends TestCase
{
    /**
     * @test
     */
    #[Test]
    public function can_hydrate_scalar(): void
    {
        $value = 'Hello world';

        $object = new class {
            public string $foo = '';
        };

        Hydrator::set($object, 'foo', $value);

        $this->assertSame($value, $object->foo);
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_scalar_array(): void
    {
        $value = ['foo', 'bar'];

        $object = new class {
            public array $foo = [];
        };

        Hydrator::set($object, 'foo', $value);

        $this->assertSame($value, $object->foo);
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_object(): void
    {
        $object = new class {
            public Object1 $foo;

            public function __construct()
            {
                $this->foo = new Object1('nope');
            }
        };

        $value = new Object1('foo');

        Hydrator::set($object, 'foo', $value);

        $this->assertSame($value, $object->foo);
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_object_array(): void
    {
        $object = new class {
            /** @var Object1[] */
            public array $foo = [];
        };

        $value = [
            new Object1('foo'),
            new Object1('bar'),
        ];

        Hydrator::set($object, 'foo', $value);

        $this->assertSame($value, $object->foo);
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_doctrine_collection(): void
    {
        $object = new class {
            /** @var Collection<array-key, Object1> */
            public Collection $foo;

            public function __construct()
            {
                $this->foo = new ArrayCollection();
            }
        };

        $value = [
            new Object1('foo'),
            new Object1('bar'),
        ];

        Hydrator::set($object, 'foo', $value);

        $this->assertInstanceOf(ArrayCollection::class, $object->foo);
        $this->assertSame($value, $object->foo->toArray());
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_doctrine_collection_union(): void
    {
        $object = new class {
            /** @var Collection<array-key, Object1>|Selectable<array-key, Object1> */
            public Collection|Selectable $foo;

            public function __construct()
            {
                $this->foo = new ArrayCollection();
            }
        };

        $value = [
            new Object1('foo'),
            new Object1('bar'),
        ];

        Hydrator::set($object, 'foo', $value);

        $this->assertInstanceOf(ArrayCollection::class, $object->foo);
        $this->assertSame($value, $object->foo->toArray());
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_doctrine_collection_intersection(): void
    {
        $object = new class {
            /** @var Collection<array-key, Object1>&Selectable<array-key, Object1> */
            public Collection&Selectable $foo;

            public function __construct()
            {
                $this->foo = new ArrayCollection();
            }
        };

        $value = [
            new Object1('foo'),
            new Object1('bar'),
        ];

        Hydrator::set($object, 'foo', $value);

        $this->assertInstanceOf(ArrayCollection::class, $object->foo);
        $this->assertSame($value, $object->foo->toArray());
    }
}
