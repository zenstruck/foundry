<?php

use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObjectFactory;
use Zenstruck\Foundry\Object\ObjectFactory;
use function Zenstruck\Foundry\anonymous;
use function Zenstruck\Foundry\create;
use function Zenstruck\Foundry\create_many;

/**
 * @param ObjectFactory<SomeObject> $factory
 * @return ObjectFactory<SomeObject>
 */
function factory(ObjectFactory $factory): ObjectFactory
{
    return $factory;
}

function object(SomeObject $object): SomeObject
{
    return $object;
}

/**
 * @param list<SomeObject> $objects
 * @return list<SomeObject>
 */
function objects(array $objects): array
{
    return $objects;
}

/**
 * @param FactoryCollection<SomeObject> $factoryCollection
 * @return FactoryCollection<SomeObject>
 */
function factory_collection(FactoryCollection $factoryCollection): FactoryCollection
{
    return $factoryCollection;
}

factory(SomeObjectFactory::new());
factory(anonymous(SomeObject::class));

object(SomeObjectFactory::new()->create());
object(SomeObjectFactory::new()->create(['title' => 'foo']));
object(SomeObjectFactory::new()->create(fn() => ['title' => 'foo']));
object(SomeObjectFactory::createOne());
object(anonymous(SomeObject::class)->create());
object(create(SomeObject::class));

objects(SomeObjectFactory::new()->many(2)->create());
objects(SomeObjectFactory::createMany(2));
objects(anonymous(SomeObject::class)->many(2)->create());
objects(create_many(2, SomeObject::class));

factory_collection(SomeObjectFactory::new()->many(2));
factory_collection(SomeObjectFactory::new()->sequence([['title' => 'foo']]));
factory_collection(SomeObjectFactory::new()->sequence(fn() => [['title' => 'foo']]));
factory_collection(anonymous(SomeObject::class)->many(2));
factory_collection(anonymous(SomeObject::class)->sequence([['title' => 'foo']]));
