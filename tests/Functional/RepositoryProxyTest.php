<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use function Zenstruck\Foundry\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class RepositoryProxyTest extends KernelTestCase
{
    use ExpectDeprecationTrait, Factories, ResetDatabase;

    /**
     * @test
     * @group legacy
     */
    public function assertions(): void
    {
        $repository = repository($this->categoryClass());

        $repository->assertEmpty();

        $this->categoryFactoryClass()::createMany(2);

        $repository->assertCount(2);
        $repository->assertCountGreaterThan(1);
        $repository->assertCountGreaterThanOrEqual(2);
        $repository->assertCountLessThan(3);
        $repository->assertCountLessThanOrEqual(2);
    }

    /**
     * @test
     */
    public function can_fetch_objects(): void
    {
        $repository = repository($this->categoryClass());

        $this->categoryFactoryClass()::createMany(2);

        $objects = $repository->findAll();

        $this->assertCount(2, $objects);
        $this->assertInstanceOf(Proxy::class, $objects[0]);

        $objects = $repository->findBy([]);

        $this->assertCount(2, $objects);
        $this->assertInstanceOf(Proxy::class, $objects[0]);
    }

    /**
     * @test
     */
    public function find_can_be_passed_proxy_or_object_or_array(): void
    {
        $repository = repository($this->categoryClass());
        $proxy = $this->categoryFactoryClass()::createOne(['name' => 'foo']);

        $this->assertInstanceOf(Proxy::class, $repository->find(['name' => 'foo']));

        if (Category::class === $this->categoryClass()) {
            $this->assertInstanceOf(Proxy::class, $repository->find($proxy));
            $this->assertInstanceOf(Proxy::class, $repository->find($proxy->object()));
        }
    }

    /**
     * @test
     */
    public function can_find_random_object(): void
    {
        $this->categoryFactoryClass()::createMany(5);

        $ids = [];

        while (5 !== \count(\array_unique($ids))) {
            $ids[] = repository($this->categoryClass())->random()->getId();
        }

        $this->assertCount(5, \array_unique($ids));
    }

    /**
     * @test
     */
    public function at_least_one_object_must_exist_to_get_random_object(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('At least 1 "%s" object(s) must have been persisted (0 persisted).', $this->categoryClass()));

        repository($this->categoryClass())->random();
    }

    /**
     * @test
     */
    public function can_find_random_set_of_objects(): void
    {
        $this->categoryFactoryClass()::createMany(5);

        $objects = repository($this->categoryClass())->randomSet(3);

        $this->assertCount(3, $objects);
        $this->assertCount(
            3,
            \array_unique(
                \array_map(
                    static function($category) {
                        return $category->getId();
                    },
                    $objects
                )
            )
        );
    }

    /**
     * @test
     */
    public function random_set_number_must_be_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$number must be positive (-1 given).');

        repository($this->categoryClass())->randomSet(-1);
    }

    /**
     * @test
     */
    public function the_number_of_persisted_objects_must_be_at_least_the_random_set_number(): void
    {
        $this->categoryFactoryClass()::createOne();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('At least 2 "%s" object(s) must have been persisted (1 persisted).', $this->categoryClass()));

        repository($this->categoryClass())->randomSet(2);
    }

    /**
     * @test
     */
    public function can_find_random_range_of_objects(): void
    {
        $this->categoryFactoryClass()::createMany(5);

        $counts = [];

        while (4 !== \count(\array_unique($counts))) {
            $counts[] = \count(repository($this->categoryClass())->randomRange(0, 3));
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
    public function the_number_of_persisted_objects_must_be_at_least_the_random_range_max(): void
    {
        $this->categoryFactoryClass()::createOne();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('At least 2 "%s" object(s) must have been persisted (1 persisted).', $this->categoryClass()));

        repository($this->categoryClass())->randomRange(0, 2);
    }

    /**
     * @test
     */
    public function random_range_min_cannot_be_less_than_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$min must be positive (-1 given).');

        repository($this->categoryClass())->randomRange(-1, 3);
    }

    /**
     * @test
     */
    public function random_set_max_cannot_be_less_than_min(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$max (3) cannot be less than $min (5).');

        repository($this->categoryClass())->randomRange(5, 3);
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
        $repository = $categoryFactoryClass::repository();

        $this->assertSame($categoryA->getId(), $repository->first()->getId());
        $this->assertSame($categoryC->getId(), $repository->first('name')->getId());
        $this->assertSame($categoryC->getId(), $repository->last()->getId());
        $this->assertSame($categoryA->getId(), $repository->last('name')->getId());
    }

    /**
     * @test
     */
    public function first_and_last_return_null_if_empty(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $this->assertNull($categoryFactoryClass::repository()->first());
        $this->assertNull($categoryFactoryClass::repository()->first('name'));
        $this->assertNull($categoryFactoryClass::repository()->last());
        $this->assertNull($categoryFactoryClass::repository()->last('name'));
    }

    /**
     * @test
     */
    public function repository_proxy_is_countable_and_iterable(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createMany(4);

        $repository = $categoryFactoryClass::repository();

        $this->assertCount(4, $repository);
        $this->assertCount(4, \iterator_to_array($repository));
    }

    /**
     * @test
     * @group legacy
     */
    public function can_use_get_count(): void
    {
        $categoryFactoryClass = $this->categoryFactoryClass();

        $categoryFactoryClass::createMany(4);

        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using RepositoryProxy::getCount() is deprecated, use RepositoryProxy::count() (it is now Countable).');

        $this->assertSame(4, $categoryFactoryClass::repository()->getCount());
    }

    abstract protected function categoryClass(): string;

    abstract protected function categoryFactoryClass(): string;
}
