<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\AnonymousFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class AnonymousFactoryTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     */
    public function can_find_or_create(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass());

        $factory->assert()->count(0);
        $factory->findOrCreate(['name' => 'php']);
        $factory->assert()->count(1);
        $factory->findOrCreate(['name' => 'php']);
        $factory->assert()->count(1);
    }

    /**
     * @test
     */
    public function can_find_random_object(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass(), ['name' => 'php']);
        $factory->many(5)->create();

        $ids = [];

        while (5 !== \count(\array_unique($ids))) {
            $ids[] = $factory->random()->getId();
        }

        $this->assertCount(5, \array_unique($ids));
    }

    /**
     * @test
     */
    public function can_create_random_object_if_none_exists(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass(), ['name' => 'php']);

        $factory->assert()->count(0);
        $this->assertInstanceOf($this->categoryClass(), $factory->randomOrCreate()->object());
        $factory->assert()->count(1);
        $this->assertInstanceOf($this->categoryClass(), $factory->randomOrCreate()->object());
        $factory->assert()->count(1);
    }

    /**
     * @test
     */
    public function can_get_or_create_random_object_with_attributes(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass(), ['name' => 'name1']);
        $factory->many(5)->create();

        $factory->assert()->count(5);
        $this->assertSame('name2', $factory->randomOrCreate(['name' => 'name2'])->getName());
        $factory->assert()->count(6);
        $this->assertSame('name2', $factory->randomOrCreate(['name' => 'name2'])->getName());
        $factory->assert()->count(6);
    }

    /**
     * @test
     */
    public function can_find_random_set_of_objects(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass(), ['name' => 'php']);
        $factory->many(5)->create();

        $objects = $factory->randomSet(3);

        $this->assertCount(3, $objects);
        $this->assertCount(3, \array_unique(\array_map(static function($category) { return $category->getId(); }, $objects)));
    }

    /**
     * @test
     */
    public function can_find_random_set_of_objects_with_attributes(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass());
        $factory->many(20)->create(['name' => 'name1']);
        $factory->many(5)->create(['name' => 'name2']);

        $objects = $factory->randomSet(2, ['name' => 'name2']);

        $this->assertCount(2, $objects);
        $this->assertSame('name2', $objects[0]->getName());
        $this->assertSame('name2', $objects[1]->getName());
    }

    /**
     * @test
     */
    public function can_find_random_range_of_objects(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass(), ['name' => 'php']);
        $factory->many(5)->create();

        $counts = [];

        while (4 !== \count(\array_unique($counts))) {
            $counts[] = \count($factory->randomRange(0, 3));
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
        $factory = AnonymousFactory::new($this->categoryClass());
        $factory->many(20)->create(['name' => 'name1']);
        $factory->many(5)->create(['name' => 'name2']);

        $objects = $factory->randomRange(2, 4, ['name' => 'name2']);

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
        $factory = AnonymousFactory::new($this->categoryClass());
        $categoryA = $factory->create(['name' => '3']);
        $categoryB = $factory->create(['name' => '2']);
        $categoryC = $factory->create(['name' => '1']);

        $this->assertSame($categoryA->getId(), $factory->first()->getId());
        $this->assertSame($categoryC->getId(), $factory->first('name')->getId());
        $this->assertSame($categoryC->getId(), $factory->last()->getId());
        $this->assertSame($categoryA->getId(), $factory->last('name')->getId());
    }

    /**
     * @test
     */
    public function first_throws_exception_if_no_entities_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        AnonymousFactory::new($this->categoryClass())->first();
    }

    /**
     * @test
     */
    public function last_throws_exception_if_no_entities_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        AnonymousFactory::new($this->categoryClass())->last();
    }

    /**
     * @test
     */
    public function can_count_and_truncate_model_factory(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass(), ['name' => 'php']);

        $this->assertSame(0, $factory->count());
        $this->assertCount(0, $factory);

        $factory->many(4)->create();

        $this->assertSame(4, $factory->count());
        $this->assertCount(4, $factory);

        $factory->truncate();

        $this->assertSame(0, $factory->count());
        $this->assertCount(0, $factory);
    }

    /**
     * @test
     */
    public function can_get_all_entities(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass(), ['name' => 'php']);

        $this->assertSame([], $factory->all());

        $factory->many(4)->create();

        $categories = $factory->all();

        $this->assertCount(4, $categories);
        $this->assertCount(4, \iterator_to_array($factory));
        $this->assertInstanceOf($this->categoryClass(), $categories[0]->object());
        $this->assertInstanceOf($this->categoryClass(), $categories[1]->object());
        $this->assertInstanceOf($this->categoryClass(), $categories[2]->object());
        $this->assertInstanceOf($this->categoryClass(), $categories[3]->object());
    }

    /**
     * @test
     */
    public function can_find_entity(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass());

        $factory->create(['name' => 'first']);
        $factory->create(['name' => 'second']);
        $category = $factory->create(['name' => 'third']);

        $this->assertSame('second', $factory->find(['name' => 'second'])->getName());
        $this->assertSame('third', $factory->find(['id' => $category->getId()])->getName());
        $this->assertSame('third', $factory->find($category->getId())->getName());

        if ($this instanceof ODMAnonymousFactoryTest) {
            return;
        }

        $this->assertSame('third', $factory->find($category->object())->getName());
        $this->assertSame('third', $factory->find($category)->getName());
    }

    /**
     * @test
     */
    public function find_throws_exception_if_no_entities_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        AnonymousFactory::new($this->categoryClass())->find(99);
    }

    /**
     * @test
     */
    public function can_find_by(): void
    {
        $factory = AnonymousFactory::new($this->categoryClass());

        $this->assertSame([], $factory->findBy(['name' => 'name2']));

        $factory->create(['name' => 'name1']);
        $factory->create(['name' => 'name2']);
        $factory->create(['name' => 'name2']);

        $categories = $factory->findBy(['name' => 'name2']);

        $this->assertCount(2, $categories);
        $this->assertSame('name2', $categories[0]->getName());
        $this->assertSame('name2', $categories[1]->getName());
    }

    abstract protected function categoryClass(): string;
}
