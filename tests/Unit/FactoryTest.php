<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Faker;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\PersistenceManager;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\ResetGlobals;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryTest extends TestCase
{
    use ResetGlobals;

    /**
     * @test
     */
    public function can_instantiate_object(): void
    {
        $attributeArray = ['title' => 'title', 'body' => 'body'];
        $attributeCallback = fn(Faker\Generator $faker) => ['title' => 'title', 'body' => 'body'];

        $this->assertSame('title', (new Factory(Post::class, $attributeArray))->instantiate()->getTitle());
        $this->assertSame('title', (new Factory(Post::class))->instantiate($attributeArray)->getTitle());
        $this->assertSame('title', (new Factory(Post::class))->withAttributes($attributeArray)->instantiate()->getTitle());
        $this->assertSame('title', (new Factory(Post::class, $attributeCallback))->instantiate()->getTitle());
        $this->assertSame('title', (new Factory(Post::class))->instantiate($attributeCallback)->getTitle());
        $this->assertSame('title', (new Factory(Post::class))->withAttributes($attributeCallback)->instantiate()->getTitle());
    }

    /**
     * @test
     */
    public function can_instantiate_many_objects(): void
    {
        $attributeArray = ['title' => 'title', 'body' => 'body'];
        $attributeCallback = fn(Faker\Generator $faker) => ['title' => 'title', 'body' => 'body'];

        $objects = (new Factory(Post::class, $attributeArray))->instantiateMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->instantiateMany(3, $attributeArray);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->withAttributes($attributeArray)->instantiateMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class, $attributeCallback))->instantiateMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->instantiateMany(3, $attributeCallback);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->withAttributes($attributeCallback)->instantiateMany(3);

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

        $object = (new Factory(Post::class))->instantiator(function(array $attributes, string $class) use ($attributeArray) {
            $this->assertSame(Post::class, $class);
            $this->assertSame($attributes, $attributeArray);

            return new Post('title', 'body');
        })->instantiate($attributeArray);

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
            ->instantiate($attributeArray)
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

        (new Factory(Post::class))->beforeInstantiate(function() {})->instantiate();
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
            ->instantiate($attributesArray)
        ;

        $this->assertSame(2, $object->getViewCount());
    }

    /**
     * @test
     */
    public function can_register_custom_faker(): void
    {
        $faker = Factory::faker();

        Factory::registerFaker(new Faker\Generator());

        $this->assertNotSame(\spl_object_id(Factory::faker()), \spl_object_id($faker));
    }

    /**
     * @test
     */
    public function can_register_default_instantiator(): void
    {
        Factory::registerDefaultInstantiator(function() {
            return new Post('different title', 'different body');
        });

        $object = (new Factory(Post::class, ['title' => 'title', 'body' => 'body']))->instantiate();

        $this->assertSame('different title', $object->getTitle());
        $this->assertSame('different body', $object->getBody());
    }

    /**
     * @test
     */
    public function instantiating_with_proxy_attribute_normalizes_to_underlying_object(): void
    {
        $object = (new Factory(Post::class))->instantiate([
            'title' => 'title',
            'body' => 'body',
            'category' => (new Proxy(new Category()))->withoutAutoRefresh(),
        ]);

        $this->assertInstanceOf(Category::class, $object->getCategory());
    }

    /**
     * @test
     */
    public function instantiating_with_factory_attribute_instantiates_the_factory(): void
    {
        $object = (new Factory(Post::class))->instantiate([
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
        $this->assertNotSame(\spl_object_id($factory->instantiator(function() {})), $objectId);
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
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        PersistenceManager::register($registry);

        $object = (new Factory(Post::class))->create(['title' => 'title', 'body' => 'body']);

        $this->assertInstanceOf(Proxy::class, $object);
        $this->assertSame('title', $object->withoutAutoRefresh()->getTitle());
    }

    /**
     * @test
     */
    public function can_create_many_objects(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->exactly(3))
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        PersistenceManager::register($registry);

        $objects = (new Factory(Post::class))->createMany(3, ['title' => 'title', 'body' => 'body']);

        $this->assertCount(3, $objects);
        $this->assertInstanceOf(Proxy::class, $objects[0]);
        $this->assertInstanceOf(Proxy::class, $objects[1]);
        $this->assertInstanceOf(Proxy::class, $objects[2]);
        $this->assertSame('title', $objects[0]->withoutAutoRefresh()->getTitle());
        $this->assertSame('title', $objects[1]->withoutAutoRefresh()->getTitle());
        $this->assertSame('title', $objects[2]->withoutAutoRefresh()->getTitle());
    }

    /**
     * @test
     */
    public function can_disable_proxy_when_creating(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        PersistenceManager::register($registry);

        $object = (new Factory(Post::class))->create(['title' => 'title', 'body' => 'body'], false);

        $this->assertInstanceOf(Post::class, $object);
        $this->assertSame('title', $object->getTitle());
    }

    /**
     * @test
     */
    public function can_globally_disable_proxying(): void
    {
        Factory::proxyByDefault(false);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        PersistenceManager::register($registry);

        $object = (new Factory(Post::class))->create(['title' => 'title', 'body' => 'body']);

        $this->assertInstanceOf(Post::class, $object);
        $this->assertSame('title', $object->getTitle());
    }

    /**
     * @test
     */
    public function creating_with_factory_attribute_persists_the_factory(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->withConsecutive([Category::class], [Post::class])
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        PersistenceManager::register($registry);

        $object = (new Factory(Post::class))->create([
            'title' => 'title',
            'body' => 'body',
            'category' => new Factory(Category::class),
        ]);

        $this->assertInstanceOf(Proxy::class, $object);
        $this->assertInstanceOf(Category::class, $object->withoutAutoRefresh()->getCategory());
    }

    /**
     * @test
     */
    public function can_add_after_persist_events(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->exactly(3)) // once for persisting, once for each afterPersist event
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        PersistenceManager::register($registry);

        $attributesArray = ['title' => 'title', 'body' => 'body'];

        $object = (new Factory(Post::class))
            ->afterPersist(function(Post $post, array $attributes) use ($attributesArray) {
                $this->assertSame($attributesArray, $attributes);

                $post->increaseViewCount();
            })
            ->afterPersist(function(Post $post, array $attributes) use ($attributesArray) {
                $this->assertSame($attributesArray, $attributes);

                $post->increaseViewCount();
            })
            ->create($attributesArray)
        ;

        $this->assertSame(2, $object->withoutAutoRefresh()->getViewCount());
    }
}
