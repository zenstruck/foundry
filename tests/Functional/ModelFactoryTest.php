<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ModelFactoryTest extends KernelTestCase
{
    use ContainerBC, Factories, ResetDatabase;

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
        $this->assertInstanceOf($this->categoryClass(), $categoryFactoryClass::randomOrCreate()->object());
        $categoryFactoryClass::assert()->count(1);
        $this->assertInstanceOf($this->categoryClass(), $categoryFactoryClass::randomOrCreate()->object());
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
        $this->assertCount(3, \array_unique(\array_map(static function($category) { return $category->getId(); }, $objects)));
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
        $categoryB = $categoryFactoryClass::createOne(['name' => '2']);
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
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createOne(['name' => 'first']);
        $categoryFactoryClass::createOne(['name' => 'second']);
        $category = $categoryFactoryClass::createOne(['name' => 'third']);

        $this->assertSame('second', $categoryFactoryClass::find(['name' => 'second'])->getName());
        $this->assertSame('third', $categoryFactoryClass::find(['id' => $category->getId()])->getName());
        $this->assertSame('third', $categoryFactoryClass::find($category->getId())->getName());

        if ($this instanceof ORMModelFactoryTest) {
            $this->assertSame('third', $categoryFactoryClass::find($category)->getName());
            $this->assertSame('third', $categoryFactoryClass::find($category->object())->getName());
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

    abstract protected function categoryClass(): string;

    abstract protected function categoryFactoryClass(): string;
}
