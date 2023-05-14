<?php

use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObjectFactory;
use function PHPStan\Testing\assertType;
use function Zenstruck\Foundry\anonymous;
use function Zenstruck\Foundry\create;
use function Zenstruck\Foundry\create_many;

assertType('Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>', PostFactory::new()->create());
assertType('Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>', PostFactory::createOne());
assertType('Zenstruck\Foundry\RepositoryProxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>', PostFactory::repository());
assertType('Zenstruck\Foundry\FactoryCollection<Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>>', PostFactory::new()->many(2));
assertType('array<int, Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>>', PostFactory::new()->many(2)->create());
assertType('array<int, Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>>', PostFactory::createMany(2));

assertType('Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject', SomeObjectFactory::new()->create());
assertType('Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject', SomeObjectFactory::createOne());
assertType('Zenstruck\Foundry\FactoryCollection<Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject>', SomeObjectFactory::new()->many(2));
assertType('array<int, Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject>', SomeObjectFactory::new()->many(2)->create());
assertType('array<int, Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject>', SomeObjectFactory::createMany(2));

assertType('Zenstruck\Foundry\Persistence\PersistentObjectFactory<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>', anonymous(Post::class));
assertType('Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>', anonymous(Post::class)->create());
assertType('Zenstruck\Foundry\FactoryCollection<Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>>', anonymous(Post::class)->many(2));
assertType('array<int, Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>>', anonymous(Post::class)->many(2)->create());
assertType('Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>', create(Post::class));
assertType('Zenstruck\Foundry\FactoryCollection<Zenstruck\Foundry\Proxy<Zenstruck\Foundry\Tests\Fixtures\Entity\Post>>', create_many(2, Post::class));

assertType('Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject', anonymous(SomeObject::class)->create());
assertType('Zenstruck\Foundry\FactoryCollection<Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject>', anonymous(SomeObject::class)->many(2));
assertType('array<int, Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject>', anonymous(SomeObject::class)->many(2)->create());
assertType('Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject', create(SomeObject::class));
assertType('Zenstruck\Foundry\FactoryCollection<Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject>', create_many(2, SomeObject::class));
