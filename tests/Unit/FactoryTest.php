<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\UnitTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function can_instantiate_object(): void
    {
        $attributeArray = ['title' => 'title', 'body' => 'body'];
        $attributeCallback = static function(Faker\Generator $faker) {
            return ['title' => 'title', 'body' => 'body'];
        };

        $this->assertSame('title', (new Factory(Post::class, $attributeArray))->withoutPersisting()->create()->getTitle());
        $this->assertSame('title', (new Factory(Post::class))->withoutPersisting()->create($attributeArray)->getTitle());
        $this->assertSame('title', (new Factory(Post::class))->withoutPersisting()->withAttributes($attributeArray)->create()->getTitle());
        $this->assertSame('title', (new Factory(Post::class, $attributeCallback))->withoutPersisting()->create()->getTitle());
        $this->assertSame('title', (new Factory(Post::class))->withoutPersisting()->create($attributeCallback)->getTitle());
        $this->assertSame('title', (new Factory(Post::class))->withAttributes($attributeCallback)->withoutPersisting()->create()->getTitle());
    }

    /**
     * @test
     */
    public function can_instantiate_many_objects(): void
    {
        $attributeArray = ['title' => 'title', 'body' => 'body'];
        $attributeCallback = static function(Faker\Generator $faker) {
            return ['title' => 'title', 'body' => 'body'];
        };

        $objects = (new Factory(Post::class, $attributeArray))->withoutPersisting()->createMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->withoutPersisting()->createMany(3, $attributeArray);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->withAttributes($attributeArray)->withoutPersisting()->createMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class, $attributeCallback))->withoutPersisting()->createMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->withoutPersisting()->createMany(3, $attributeCallback);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->withAttributes($attributeCallback)->withoutPersisting()->createMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());
    }

    /**
     * @test
     */
    public function can_set_instantiator(): void
    {
        $attributeArray = ['title' => 'original title', 'body' => 'original body'];

        $object = (new Factory(Post::class))
            ->instantiateWith(function(array $attributes, string $class) use ($attributeArray) {
                $this->assertSame(Post::class, $class);
                $this->assertSame($attributes, $attributeArray);

                return new Post('title', 'body');
            })
            ->withoutPersisting()
            ->create($attributeArray)
        ;

        $this->assertSame('title', $object->getTitle());
        $this->assertSame('body', $object->getBody());
    }

    /**
     * @test
     */
    public function can_add_before_instantiate_events(): void
    {
        $attributeArray = ['title' => 'original title', 'body' => 'original body'];

        $object = (new Factory(Post::class))
            ->beforeInstantiate(function(array $attributes) {
                $attributes['title'] = 'title';

                return $attributes;
            })
            ->beforeInstantiate(function(array $attributes) {
                $attributes['body'] = 'body';

                return $attributes;
            })
            ->withoutPersisting()
            ->create($attributeArray)
        ;

        $this->assertSame('title', $object->getTitle());
        $this->assertSame('body', $object->getBody());
    }

    /**
     * @test
     */
    public function before_instantiate_event_must_return_an_array(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Before Instantiate event callback must return an array.');

        (new Factory(Post::class))->beforeInstantiate(function() {})->withoutPersisting()->create();
    }

    /**
     * @test
     */
    public function can_add_after_instantiate_events(): void
    {
        $attributesArray = ['title' => 'title', 'body' => 'body'];

        $object = (new Factory(Post::class))
            ->afterInstantiate(function(Post $post, array $attributes) use ($attributesArray) {
                $this->assertSame($attributesArray, $attributes);

                $post->increaseViewCount();
            })
            ->afterInstantiate(function(Post $post, array $attributes) use ($attributesArray) {
                $this->assertSame($attributesArray, $attributes);

                $post->increaseViewCount();
            })
            ->withoutPersisting()
            ->create($attributesArray)
        ;

        $this->assertSame(2, $object->getViewCount());
    }

    /**
     * @test
     */
    public function can_register_custom_faker(): void
    {
        $defaultFaker = Factory::faker();
        Factory::configuration()->setFaker(new Faker\Generator());

        $this->assertNotSame(\spl_object_id(Factory::faker()), \spl_object_id($defaultFaker));
    }

    /**
     * @test
     */
    public function can_register_default_instantiator(): void
    {
        $this->configuration->setInstantiator(function() {
            return new Post('different title', 'different body');
        });

        $object = (new Factory(Post::class, ['title' => 'title', 'body' => 'body']))->withoutPersisting()->create();

        $this->assertSame('different title', $object->getTitle());
        $this->assertSame('different body', $object->getBody());
    }

    /**
     * @test
     */
    public function instantiating_with_proxy_attribute_normalizes_to_underlying_object(): void
    {
        $object = (new Factory(Post::class))->withoutPersisting()->create([
            'title' => 'title',
            'body' => 'body',
            'category' => new Proxy(new Category()),
        ]);

        $this->assertInstanceOf(Category::class, $object->getCategory());
    }

    /**
     * @test
     */
    public function instantiating_with_factory_attribute_instantiates_the_factory(): void
    {
        $object = (new Factory(Post::class))->withoutPersisting()->create([
            'title' => 'title',
            'body' => 'body',
            'category' => new Factory(Category::class),
        ]);

        $this->assertInstanceOf(Category::class, $object->getCategory());
    }

    /**
     * @test
     */
    public function factory_is_immutable(): void
    {
        $factory = new Factory(Post::class);
        $objectId = \spl_object_id($factory);

        $this->assertNotSame(\spl_object_id($factory->withAttributes([])), $objectId);
        $this->assertNotSame(\spl_object_id($factory->withoutPersisting()), $objectId);
        $this->assertNotSame(\spl_object_id($factory->instantiateWith(function() {})), $objectId);
        $this->assertNotSame(\spl_object_id($factory->beforeInstantiate(function() {})), $objectId);
        $this->assertNotSame(\spl_object_id($factory->afterInstantiate(function() {})), $objectId);
        $this->assertNotSame(\spl_object_id($factory->afterPersist(function() {})), $objectId);
    }

    /**
     * @test
     */
    public function can_create_object(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        $this->configuration->setManagerRegistry($registry);

        $object = (new Factory(Post::class))->create(['title' => 'title', 'body' => 'body']);

        $this->assertInstanceOf(Proxy::class, $object);
        $this->assertSame('title', $object->getTitle());
    }

    /**
     * @test
     */
    public function can_create_many_objects(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->exactly(6))
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        $this->configuration->setManagerRegistry($registry);

        $objects = (new Factory(Post::class))->createMany(3, ['title' => 'title', 'body' => 'body']);

        $this->assertCount(3, $objects);
        $this->assertInstanceOf(Proxy::class, $objects[0]);
        $this->assertInstanceOf(Proxy::class, $objects[1]);
        $this->assertInstanceOf(Proxy::class, $objects[2]);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());
    }

    /**
     * @test
     */
    public function can_add_after_persist_events(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->exactly(2)) // once for persisting, once for each afterPersist event
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        $this->configuration->setManagerRegistry($registry);

        $expectedAttributes = ['short_description' => 'short desc', 'title' => 'title', 'body' => 'body'];
        $calls = 0;

        $object = (new Factory(Post::class, ['short_description' => 'short desc']))
            ->afterPersist(function(Proxy $post, array $attributes) use ($expectedAttributes, &$calls) {
                /* @var Post $post */
                $this->assertSame($expectedAttributes, $attributes);

                $post->increaseViewCount();
                ++$calls;
            })
            ->afterPersist(function(Post $post, array $attributes) use ($expectedAttributes, &$calls) {
                $this->assertSame($expectedAttributes, $attributes);

                $post->increaseViewCount();
                ++$calls;
            })
            ->afterPersist(function(Post $post, array $attributes) use ($expectedAttributes, &$calls) {
                $this->assertSame($expectedAttributes, $attributes);

                $post->increaseViewCount();
                ++$calls;
            })
            ->afterPersist(function($post) use (&$calls) {
                $this->assertInstanceOf(Proxy::class, $post);

                ++$calls;
            })
            ->afterPersist(static function() use (&$calls) {
                ++$calls;
            })
            ->create(['title' => 'title', 'body' => 'body'])
        ;

        $this->assertSame(3, $object->getViewCount());
        $this->assertSame(5, $calls);
    }
}
