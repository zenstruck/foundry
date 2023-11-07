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

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Faker;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\LegacyPostFactory;

use function Zenstruck\Foundry\anonymous;
use function Zenstruck\Foundry\lazy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    public function can_instantiate_object(): void
    {
        $attributeArray = ['title' => 'title', 'body' => 'body'];
        $attributeCallback = static fn(): array => ['title' => 'title', 'body' => 'body'];
        $attributeArrayWithLazyValue = ['title' => lazy(fn() => 'title'), 'body' => 'body'];

        $this->assertSame('title', anonymous(Post::class, $attributeArray)->create()->getTitle());
        $this->assertSame('title', anonymous(Post::class)->create($attributeArray)->getTitle());
        $this->assertSame('title', anonymous(Post::class)->with($attributeArray)->create()->getTitle());
        $this->assertSame('title', anonymous(Post::class, $attributeCallback)->create()->getTitle());
        $this->assertSame('title', anonymous(Post::class)->create($attributeCallback)->getTitle());
        $this->assertSame('title', anonymous(Post::class)->with($attributeCallback)->create()->getTitle());
        $this->assertSame('title', anonymous(Post::class, $attributeArrayWithLazyValue)->create()->getTitle());
        $this->assertSame('title', anonymous(Post::class)->create($attributeArrayWithLazyValue)->getTitle());
        $this->assertSame('title', anonymous(Post::class)->with($attributeArrayWithLazyValue)->create()->getTitle());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_use_legacy_with_attributes(): void
    {
        $this->assertSame('title', anonymous(Post::class)->withAttributes(['title' => 'title', 'body' => 'body'])->create()->getTitle());
    }

    /**
     * @test
     */
    public function lazy_values_are_only_calculated_if_needed(): void
    {
        $count = 0;
        $lazyValue = LazyValue::new(function() use (&$count) {
            ++$count;

            return 'title';
        });
        $factory = anonymous(Post::class, ['title' => $lazyValue, 'body' => 'body']);

        $post = $factory
            ->with(['title' => $lazyValue])
            ->with(['title' => $lazyValue])
            ->create(['title' => 'title'])
        ;

        $this->assertSame('title', $post->getTitle());
        $this->assertSame(0, $count);

        $post = $factory
            ->with(['title' => $lazyValue])
            ->with(['title' => $lazyValue])
            ->create(['title' => $lazyValue])
        ;

        $this->assertSame('title', $post->getTitle());
        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function lazy_memoized_values_are_only_calculated_once(): void
    {
        $count = 0;
        $lazyValue = LazyValue::memoize(function() use (&$count) {
            ++$count;

            return 'title';
        });
        $factory = anonymous(Post::class, ['title' => $lazyValue, 'body' => 'body']);

        $posts = $factory
            ->many(3)
            ->create()
        ;

        foreach ($posts as $post) {
            $this->assertSame('title', $post->getTitle());
        }

        $this->assertSame(1, $count);
    }

    /**
     * @test
     * @group legacy
     */
    public function can_instantiate_many_objects_legacy(): void
    {
        $attributeArray = ['title' => 'title', 'body' => 'body'];
        $attributeCallback = static fn(): array => ['title' => 'title', 'body' => 'body'];

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

        $objects = (new Factory(Post::class))->with($attributeArray)->createMany(3);

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

        $objects = (new Factory(Post::class))->with($attributeCallback)->createMany(3);

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

        $object = anonymous(Post::class)
            ->instantiateWith(function(array $attributes, string $class) use ($attributeArray): Post {
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

        $object = anonymous(Post::class)
            ->beforeInstantiate(static function(array $attributes): array {
                $attributes['title'] = 'title';

                return $attributes;
            })
            ->beforeInstantiate(static function(array $attributes): array {
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

        anonymous(Post::class)->beforeInstantiate(static function(): void {})->create();
    }

    /**
     * @test
     */
    public function can_add_after_instantiate_events(): void
    {
        $attributesArray = ['title' => 'title', 'body' => 'body'];

        $object = anonymous(Post::class)
            ->afterInstantiate(function(Post $post, array $attributes) use ($attributesArray): void {
                $this->assertSame($attributesArray, $attributes);

                $post->increaseViewCount();
            })
            ->afterInstantiate(function(Post $post, array $attributes) use ($attributesArray): void {
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
        Factory::configuration()->setInstantiator(static fn(): Post => new Post('different title', 'different body'));

        $object = anonymous(Post::class, ['title' => 'title', 'body' => 'body'])->create();

        $this->assertSame('different title', $object->getTitle());
        $this->assertSame('different body', $object->getBody());
    }

    /**
     * @test
     */
    public function instantiating_with_proxy_attribute_normalizes_to_underlying_object(): void
    {
        $object = anonymous(Post::class)->create([
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
        $object = anonymous(Post::class)->create([
            'title' => 'title',
            'body' => 'body',
            'category' => anonymous(Category::class),
        ]);

        $this->assertInstanceOf(Category::class, $object->getCategory());
    }

    /**
     * @test
     */
    public function factory_is_immutable(): void
    {
        $factory = anonymous(Post::class);
        $objectId = \spl_object_id($factory);

        $this->assertNotSame(\spl_object_id($factory->with([])), $objectId);
        $this->assertNotSame(\spl_object_id($factory->withoutPersisting()), $objectId);
        $this->assertNotSame(\spl_object_id($factory->instantiateWith(static function(): void {})), $objectId);
        $this->assertNotSame(\spl_object_id($factory->beforeInstantiate(static function(): void {})), $objectId);
        $this->assertNotSame(\spl_object_id($factory->afterInstantiate(static function(): void {})), $objectId);
        $this->assertNotSame(\spl_object_id($factory->afterPersist(static function(): void {})), $objectId);
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

        Factory::configuration()->setManagerRegistry($registry)->disableDefaultProxyAutoRefresh();

        $object = anonymous(Post::class)->create(['title' => 'title', 'body' => 'body']);

        $this->assertInstanceOf(Proxy::class, $object);
        $this->assertSame('title', $object->getTitle());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_create_many_objects_legacy(): void
    {
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

        Factory::configuration()->setManagerRegistry($registry)->disableDefaultProxyAutoRefresh();

        $expectedAttributes = ['shortDescription' => 'short desc', 'title' => 'title', 'body' => 'body'];
        $calls = 0;

        $object = anonymous(Post::class, ['shortDescription' => 'short desc'])
            ->afterPersist(function(Proxy $post, array $attributes) use ($expectedAttributes, &$calls): void {
                /* @var Post $post */
                $this->assertSame($expectedAttributes, $attributes);

                $post->increaseViewCount();
                ++$calls;
            })
            ->afterPersist(function(Post $post, array $attributes) use ($expectedAttributes, &$calls): void {
                $this->assertSame($expectedAttributes, $attributes);

                $post->increaseViewCount();
                ++$calls;
            })
            ->afterPersist(function(Post $post, array $attributes) use ($expectedAttributes, &$calls): void {
                $this->assertSame($expectedAttributes, $attributes);

                $post->increaseViewCount();
                ++$calls;
            })
            ->afterPersist(function($post) use (&$calls): void {
                $this->assertInstanceOf(Proxy::class, $post);

                ++$calls;
            })
            ->afterPersist(static function() use (&$calls): void {
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

        anonymous(Post::class)->create(['title' => 'title', 'body' => 'body'])->_save();
    }

    /**
     * @test
     */
    public function can_use_arrays_for_attribute_values(): void
    {
        $object = new class() {
            public $value;
        };

        $factory = anonymous($object::class)->create(['value' => ['foo' => 'bar']]);

        $this->assertSame(['foo' => 'bar'], $factory->value);
    }

    /**
     * @test
     * @group legacy
     */
    public function can_use_legacy_model_factory(): void
    {
        $post = LegacyPostFactory::createOne(['title' => 'title', 'body' => 'body']);

        self::assertSame('title', $post->getTitle());
        self::assertSame('body', $post->getBody());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_use_legacy_proxy_class(): void
    {
        $post = anonymous(Post::class)->create(['title' => 'title', 'body' => 'body']);
        self::assertInstanceOf(\Zenstruck\Foundry\Proxy::class, $post);
    }
}
