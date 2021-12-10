<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Faker;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\AnonymousFactory;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryTest extends TestCase
{
    use ExpectDeprecationTrait, Factories;

    /**
     * @test
     */
    public function can_instantiate_object(): void
    {
        $attributeArray = ['title' => 'title', 'body' => 'body'];
        $attributeCallback = static function(Faker\Generator $faker) {
            return ['title' => 'title', 'body' => 'body'];
        };

        $this->assertSame('title', (new AnonymousFactory(Post::class, $attributeArray))->create()->getTitle());
        $this->assertSame('title', (new AnonymousFactory(Post::class))->create($attributeArray)->getTitle());
        $this->assertSame('title', (new AnonymousFactory(Post::class))->withAttributes($attributeArray)->create()->getTitle());
        $this->assertSame('title', (new AnonymousFactory(Post::class, $attributeCallback))->create()->getTitle());
        $this->assertSame('title', (new AnonymousFactory(Post::class))->create($attributeCallback)->getTitle());
        $this->assertSame('title', (new AnonymousFactory(Post::class))->withAttributes($attributeCallback)->create()->getTitle());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_instantiate_many_objects_legacy(): void
    {
        $this->expectDeprecation('Since zenstruck/foundry 1.7: Calling instance method "'.Factory::class.'::createMany()" is deprecated and will be removed in 2.0, use the static "'.Factory::class.':createMany()" method instead.');

        $attributeArray = ['title' => 'title', 'body' => 'body'];
        $attributeCallback = static function(Faker\Generator $faker) {
            return ['title' => 'title', 'body' => 'body'];
        };

        $objects = (new Factory(Post::class, $attributeArray))->createMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->createMany(3, $attributeArray);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->withAttributes($attributeArray)->createMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class, $attributeCallback))->createMany(3);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->createMany(3, $attributeCallback);

        $this->assertCount(3, $objects);
        $this->assertSame('title', $objects[0]->getTitle());
        $this->assertSame('title', $objects[1]->getTitle());
        $this->assertSame('title', $objects[2]->getTitle());

        $objects = (new Factory(Post::class))->withAttributes($attributeCallback)->createMany(3);

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

        $object = (new AnonymousFactory(Post::class))
            ->instantiateWith(function(array $attributes, string $class) use ($attributeArray) {
                $this->assertSame(Post::class, $class);
                $this->assertSame($attributes, $attributeArray);

                return new Post('title', 'body');
            })
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

        $object = (new AnonymousFactory(Post::class))
            ->beforeInstantiate(function(array $attributes) {
                $attributes['title'] = 'title';

                return $attributes;
            })
            ->beforeInstantiate(function(array $attributes) {
                $attributes['body'] = 'body';

                return $attributes;
            })
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

        (new AnonymousFactory(Post::class))->beforeInstantiate(function() {})->create();
    }

    /**
     * @test
     */
    public function can_add_after_instantiate_events(): void
    {
        $attributesArray = ['title' => 'title', 'body' => 'body'];

        $object = (new AnonymousFactory(Post::class))
            ->afterInstantiate(function(Post $post, array $attributes) use ($attributesArray) {
                $this->assertSame($attributesArray, $attributes);

                $post->increaseViewCount();
            })
            ->afterInstantiate(function(Post $post, array $attributes) use ($attributesArray) {
                $this->assertSame($attributesArray, $attributes);

                $post->increaseViewCount();
            })
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
        Factory::configuration()->setFaker(Faker\Factory::create());

        $this->assertNotSame(\spl_object_id(Factory::faker()), \spl_object_id($defaultFaker));
    }

    /**
     * @test
     */
    public function can_register_default_instantiator(): void
    {
        Factory::configuration()->setInstantiator(function() {
            return new Post('different title', 'different body');
        });

        $object = (new AnonymousFactory(Post::class, ['title' => 'title', 'body' => 'body']))->create();

        $this->assertSame('different title', $object->getTitle());
        $this->assertSame('different body', $object->getBody());
    }

    /**
     * @test
     */
    public function instantiating_with_proxy_attribute_normalizes_to_underlying_object(): void
    {
        $object = (new AnonymousFactory(Post::class))->create([
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
        $object = (new AnonymousFactory(Post::class))->create([
            'title' => 'title',
            'body' => 'body',
            'category' => new AnonymousFactory(Category::class),
        ]);

        $this->assertInstanceOf(Category::class, $object->getCategory());
    }

    /**
     * @test
     */
    public function factory_is_immutable(): void
    {
        $factory = new AnonymousFactory(Post::class);
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
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        Factory::configuration()->setManagerRegistry($registry);

        $object = (new AnonymousFactory(Post::class))->create(['title' => 'title', 'body' => 'body']);

        $this->assertInstanceOf(Proxy::class, $object);
        $this->assertSame('title', $object->getTitle());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_create_many_objects_legacy(): void
    {
        $this->expectDeprecation('Since zenstruck/foundry 1.7: Calling instance method "'.Factory::class.'::createMany()" is deprecated and will be removed in 2.0, use the static "'.Factory::class.':createMany()" method instead.');

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        Factory::configuration()->setManagerRegistry($registry);

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
            ->method('getManagerForClass')
            ->with(Post::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        Factory::configuration()->setManagerRegistry($registry);

        $expectedAttributes = ['shortDescription' => 'short desc', 'title' => 'title', 'body' => 'body'];
        $calls = 0;

        $object = (new AnonymousFactory(Post::class, ['shortDescription' => 'short desc']))
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

    /**
     * @test
     */
    public function trying_to_persist_without_manager_registry_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Foundry was booted without doctrine. Ensure your TestCase extends '.KernelTestCase::class);

        (new AnonymousFactory(Post::class))->create(['title' => 'title', 'body' => 'body'])->save();
    }
}
