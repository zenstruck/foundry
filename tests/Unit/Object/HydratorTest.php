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
use Zenstruck\Foundry\ForceValue;
use Zenstruck\Foundry\Object\Hydrator;
use Zenstruck\Foundry\Tests\Fixture\Object1;
use Zenstruck\Foundry\Tests\Fixture\SnapshotChild;

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

        Hydrator::forceSet($object, 'foo', $value);

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

        Hydrator::forceSet($object, 'foo', $value);

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

        Hydrator::forceSet($object, 'foo', $value);

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

        Hydrator::forceSet($object, 'foo', $value);

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

        Hydrator::forceSet($object, 'foo', $value);

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

        Hydrator::forceSet($object, 'foo', $value);

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

        Hydrator::forceSet($object, 'foo', $value);

        $this->assertInstanceOf(ArrayCollection::class, $object->foo);
        $this->assertSame($value, $object->foo->toArray());
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_with_force_value(): void
    {
        $object = new class {
            private string $foo = '';

            public function getFoo(): string
            {
                return $this->foo;
            }
        };

        (new Hydrator())($object, ['foo' => new ForceValue('foo')]);

        $this->assertSame('foo', $object->getFoo());
    }

    /**
     * @test
     */
    #[Test]
    public function can_force_set_with_force_value(): void
    {
        $object = new class {
            private string $foo = '';

            public function getFoo(): string
            {
                return $this->foo;
            }
        };

        Hydrator::forceSet($object, 'foo', new ForceValue('foo'));

        $this->assertSame('foo', $object->getFoo());
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_from_snapshot_with_public_property_only(): void
    {
        $object = new class {
            public string $foo = 'original';
        };

        $snapshot = (array) $object;
        $object->foo = 'overwritten';

        Hydrator::hydrateFromSnapshot($object, $snapshot);

        $this->assertSame('original', $object->foo);
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_from_snapshot_with_own_private_property(): void
    {
        $object = new class {
            private string $foo = 'original';

            public function getFoo(): string
            {
                return $this->foo;
            }

            public function setFoo(string $foo): void
            {
                $this->foo = $foo;
            }
        };

        $snapshot = (array) $object;
        $object->setFoo('overwritten');

        Hydrator::hydrateFromSnapshot($object, $snapshot);

        $this->assertSame('original', $object->getFoo());
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_from_snapshot_with_own_protected_property(): void
    {
        $object = new class {
            protected string $foo = 'original';

            public function getFoo(): string
            {
                return $this->foo;
            }

            public function setFoo(string $foo): void
            {
                $this->foo = $foo;
            }
        };

        $snapshot = (array) $object;
        $object->setFoo('overwritten');

        Hydrator::hydrateFromSnapshot($object, $snapshot);

        $this->assertSame('original', $object->getFoo());
    }

    /**
     * @test
     */
    #[Test]
    public function can_hydrate_from_snapshot_with_inherited_properties(): void
    {
        $object = new SnapshotChild();

        $snapshot = (array) $object;

        Hydrator::forceSet($object, 'childPublic', 'overwritten');

        Hydrator::hydrateFromSnapshot($object, $snapshot);

        $this->assertSame('child-public', $object->childPublic);
        $this->assertSame('child-private', $object->childPrivate());
        $this->assertSame('parent-private', $object->parentPrivate());
        $this->assertSame('parent-protected', $object->parentProtected());
        // a private property shadowed by a child keeps one value per declaring scope
        $this->assertSame('child-shadowed', $object->childShadowed());
        $this->assertSame('parent-shadowed', $object->parentShadowed());
    }
}
