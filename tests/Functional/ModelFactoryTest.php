<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\RepositoryAssertions;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\anonymous;
use function Zenstruck\Foundry\Persistence\disable_persisting;
use function Zenstruck\Foundry\Persistence\enable_persisting;
use function Zenstruck\Foundry\Persistence\persist;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ModelFactoryTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     */
    public function can_find_or_create(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::assert()->count(0);
        $categoryFactoryClass::findOrCreate(['name' => 'php']);
        $categoryFactoryClass::assert()->count(1);
        $categoryFactoryClass::findOrCreate(['name' => 'php']);
        $categoryFactoryClass::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_find_random_object(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createMany(5);

        $ids = [];

        while (5 !== \count(\array_unique($ids))) {
            $ids[] = $categoryFactoryClass::random()->getId();
        }

        $this->assertCount(5, \array_unique($ids));
    }

    /**
     * @test
     */
    public function can_create_random_object_if_none_exists(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::assert()->count(0);
        $this->assertInstanceOf($this->categoryClass(), $categoryFactoryClass::randomOrCreate()->_real());
        $categoryFactoryClass::assert()->count(1);
        $this->assertInstanceOf($this->categoryClass(), $categoryFactoryClass::randomOrCreate()->_real());
        $categoryFactoryClass::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_get_or_create_random_object_with_attributes(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createMany(5, ['name' => 'name1']);

        $categoryFactoryClass::assert()->count(5);
        $this->assertSame('name2', $categoryFactoryClass::randomOrCreate(['name' => 'name2'])->getName());
        $categoryFactoryClass::assert()->count(6);
        $this->assertSame('name2', $categoryFactoryClass::randomOrCreate(['name' => 'name2'])->getName());
        $categoryFactoryClass::assert()->count(6);
    }

    /**
     * @test
     */
    public function can_find_random_set_of_objects(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createMany(5);

        $objects = $categoryFactoryClass::randomSet(3);

        $this->assertCount(3, $objects);
        $this->assertCount(3, \array_unique(\array_map(static fn($category) => $category->getId(), $objects)));
    }

    /**
     * @test
     */
    public function can_find_random_set_of_objects_with_attributes(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createMany(20, ['name' => 'name1']);
        $categoryFactoryClass::createMany(5, ['name' => 'name2']);

        $objects = $categoryFactoryClass::randomSet(2, ['name' => 'name2']);

        $this->assertCount(2, $objects);
        $this->assertSame('name2', $objects[0]->getName());
        $this->assertSame('name2', $objects[1]->getName());
    }

    /**
     * @test
     */
    public function can_find_random_range_of_objects(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createMany(5);

        $counts = [];

        while (4 !== \count(\array_unique($counts))) {
            $counts[] = \count($categoryFactoryClass::randomRange(0, 3));
        }

        $this->assertCount(4, \array_unique($counts));
        $this->assertContains(0, $counts);
        $this->assertContains(1, $counts);
        $this->assertContains(2, $counts);
        $this->assertContains(3, $counts);
        $this->assertNotContains(4, $counts);
        $this->assertNotContains(5, $counts);
    }

    /**
     * @test
     */
    public function can_find_random_range_of_objects_with_attributes(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createMany(20, ['name' => 'name1']);
        $categoryFactoryClass::createMany(5, ['name' => 'name2']);

        $objects = $categoryFactoryClass::randomRange(2, 4, ['name' => 'name2']);

        $this->assertGreaterThanOrEqual(2, \count($objects));
        $this->assertLessThanOrEqual(4, \count($objects));

        foreach ($objects as $object) {
            $this->assertSame('name2', $object->getName());
        }
    }

    /**
     * @test
     */
    public function first_and_last_return_the_correct_object(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryA = $categoryFactoryClass::createOne(['name' => '3']);
        $categoryFactoryClass::createOne(['name' => '2']);
        $categoryC = $categoryFactoryClass::createOne(['name' => '1']);

        $this->assertSame($categoryA->getId(), $categoryFactoryClass::first()->getId());
        $this->assertSame($categoryC->getId(), $categoryFactoryClass::first('name')->getId());
        $this->assertSame($categoryC->getId(), $categoryFactoryClass::last()->getId());
        $this->assertSame($categoryA->getId(), $categoryFactoryClass::last('name')->getId());
    }

    /**
     * @test
     */
    public function first_throws_exception_if_no_entities_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->categoryFactoryClass()::first();
    }

    /**
     * @test
     */
    public function last_throws_exception_if_no_entities_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->categoryFactoryClass()::last();
    }

    /**
     * @test
     */
    public function can_count_and_truncate_model_factory(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $this->assertSame(0, $categoryFactoryClass::count());

        $categoryFactoryClass::createMany(4);

        $this->assertSame(4, $categoryFactoryClass::count());

        $categoryFactoryClass::truncate();

        $this->assertSame(0, $categoryFactoryClass::count());
    }

    /**
     * @test
     */
    public function can_get_all_entities(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $this->assertSame([], $categoryFactoryClass::all());

        $categoryFactoryClass::createMany(4);

        $categories = $categoryFactoryClass::all();

        $this->assertCount(4, $categories);
        $this->assertInstanceOf($this->categoryClass(), $categories[0]->_real());
        $this->assertInstanceOf($this->categoryClass(), $categories[1]->_real());
        $this->assertInstanceOf($this->categoryClass(), $categories[2]->_real());
        $this->assertInstanceOf($this->categoryClass(), $categories[3]->_real());
    }

    /**
     * @test
     */
    public function can_find_entity(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createOne(['name' => 'first']);
        $categoryFactoryClass::createOne(['name' => 'second']);
        $category = $categoryFactoryClass::createOne(['name' => 'third']);

        $this->assertSame('second', $categoryFactoryClass::find(['name' => 'second'])->getName());
        $this->assertSame('third', $categoryFactoryClass::find(['id' => $category->getId()])->getName());
        $this->assertSame('third', $categoryFactoryClass::find($category->getId())->getName());

        if ($this instanceof ORMModelFactoryTest) {
            $this->assertSame('third', $categoryFactoryClass::find($category)->getName());
            $this->assertSame('third', $categoryFactoryClass::find($category->_real())->getName());
        }
    }

    /**
     * @test
     */
    public function find_throws_exception_if_no_entities_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->categoryFactoryClass()::find(99);
    }

    /**
     * @test
     */
    public function can_find_by(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $this->assertSame([], $categoryFactoryClass::findBy(['name' => 'name2']));

        $categoryFactoryClass::createOne(['name' => 'name1']);
        $categoryFactoryClass::createOne(['name' => 'name2']);
        $categoryFactoryClass::createOne(['name' => 'name2']);

        $categories = $categoryFactoryClass::findBy(['name' => 'name2']);

        $this->assertCount(2, $categories);
        $this->assertSame('name2', $categories[0]->getName());
        $this->assertSame('name2', $categories[1]->getName());
    }

    /**
     * @test
     */
    public function resave_after_persist_events(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::new()
            ->afterPersist(static function($category): void {
                $category->setName('new');
            })
            ->create(['name' => 'original'])
        ;

        $categoryFactoryClass::assert()->exists(['name' => 'new']);
        $categoryFactoryClass::assert()->notExists(['name' => 'original']);
    }

    /**
     * @test
     */
    public function can_create_sequence(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();
        $categoryFactoryClass::createSequence([['name' => 'foo'], ['name' => 'bar']]);

        $categoryFactoryClass::assert()->exists(['name' => 'foo']);
        $categoryFactoryClass::assert()->exists(['name' => 'bar']);
    }

    /**
     * @dataProvider sequenceProvider
     * @group legacy
     */
    public function can_create_sequence_with_callable(callable $sequence): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();
        $categoryFactoryClass::createSequence($sequence);

        $categoryFactoryClass::assert()->exists(['name' => 'foo']);
        $categoryFactoryClass::assert()->exists(['name' => 'bar']);
    }

    public function sequenceProvider(): iterable
    {
        yield 'with a callable which returns an array of attributes' => [
            static fn(): array => [['name' => 'foo'], ['name' => 'bar']],
        ];

        yield 'with a callable which yields attributes' => [
            static function(): \Generator {
                yield ['name' => 'foo'];
                yield ['name' => 'bar'];
            },
        ];
    }

    /**
     * @test
     */
    public function can_create_many_objects_with_index(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();
        $categoryFactoryClass::createMany(2, static fn(int $i): array => [
            'name' => "foo {$i}",
        ]);

        $categoryFactoryClass::assert()->exists(['name' => 'foo 1']);
        $categoryFactoryClass::assert()->exists(['name' => 'foo 2']);
    }

    /**
     * @test
     * @dataProvider factoryCollectionAsDataProvider
     */
    public function can_use_factory_collection_as_data_provider(FactoryCollection $factoryCollection): void
    {
        $factoryCollection->create();
        $factoryCollection->factory()::assert()->exists(['name' => 'foo']);
    }

    public function factoryCollectionAsDataProvider(): iterable
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        yield [$categoryFactoryClass::new(['name' => 'foo'])->many(1)];
        yield [$categoryFactoryClass::new()->sequence([['name' => 'foo']])];
    }

    /**
     * @test
     * @dataProvider dataProviderYieldedFromFactoryCollection
     */
    public function can_yield_data_provider_from_factory_collection(Factory $factory): void
    {
        $factory->create();
        $factory::assert()->exists(['name' => 'foo']);
    }

    /**
     * @test
     */
    public function can_assert_count_with_criteria(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createMany(2, ['name' => 'foo']);
        $categoryFactoryClass::createMany(3, ['name' => 'bar']);

        $categoryFactoryClass::assert()->count(2, ['name' => 'foo']);
        $categoryFactoryClass::assert()->count(3, ['name' => 'bar']);
        $categoryFactoryClass::assert()->count(0, ['name' => 'baz']);

        $categoryFactoryClass::assert()->countGreaterThan(1, ['name' => 'foo']);
        $categoryFactoryClass::assert()->countGreaterThanOrEqual(2, ['name' => 'foo']);
        $categoryFactoryClass::assert()->countLessThan(3, ['name' => 'foo']);
        $categoryFactoryClass::assert()->countLessThanOrEqual(2, ['name' => 'foo']);

        $categoryFactoryClass::assert()->empty(['name' => 'baz']);
    }

    public function dataProviderYieldedFromFactoryCollection(): iterable
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        yield from $categoryFactoryClass::new(['name' => 'foo'])->many(1)->asDataProvider();
        yield from $categoryFactoryClass::new()->sequence([['name' => 'foo']])->asDataProvider();
    }

    /**
     * @test
     */
    public function can_pass_method_as_attributes(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        // pass setter
        $category = $categoryFactoryClass::createOne(['setName' => 'foo']);
        self::assertSame('foo', $category->getName());

        // pass random method
        $category = $categoryFactoryClass::createOne(['updateName' => 'another foo']);
        self::assertSame('another foo', $category->getName());
    }

    /**
     * @test
     */
    public function can_disable_persist_globally(): void
    {
        disable_persisting();

        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createOne(['name' => 'foo']);
        $categoryFactoryClass::new()->create(['name' => 'foo']);
        anonymous($this->categoryClass())->create(['name' => 'foo']);
        persist($this->categoryClass(), ['name' => 'foo']);

        enable_persisting(); // need to reactivate persist to access to RepositoryAssertions
        $categoryFactoryClass::assert()->count(0);
    }

    /**
     * @test
     */
    public function cannot_access_repository_method_when_persist_disabled(): void
    {
        disable_persisting();

        $countErrors = 0;
        try {
            $this->categoryFactoryClass()::assert();
        } catch (\RuntimeException $e) {
            ++$countErrors;
        }

        try {
            $this->categoryFactoryClass()::repository();
        } catch (\RuntimeException $e) {
            ++$countErrors;
        }

        try {
            $this->categoryFactoryClass()::findBy([]);
        } catch (\RuntimeException $e) {
            ++$countErrors;
        }

        self::assertSame(3, $countErrors);
    }

    /**
     * @test
     * @depends cannot_access_repository_method_when_persist_disabled
     */
    public function assert_persist_is_re_enabled_automatically(): void
    {
        self::assertTrue(Factory::configuration()->isPersistEnabled());

        persist($this->categoryClass(), ['name' => 'foo']);
        $this->categoryFactoryClass()::assert()->count(1);
    }

    abstract protected function categoryClass(): string;

    abstract protected function categoryFactoryClass(): string;
}
