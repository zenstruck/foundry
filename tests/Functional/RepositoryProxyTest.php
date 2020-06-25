<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\FunctionalTestCase;
use function Zenstruck\Foundry\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RepositoryProxyTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function functions_calls_are_passed_to_underlying_repository(): void
    {
        $this->assertSame('from custom method', repository(Post::class)->customMethod());
    }

    /**
     * @test
     */
    public function assertions(): void
    {
        $repository = repository(Category::class);

        $repository->assertEmpty();

        CategoryFactory::new()->createMany(2);

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
        $repository = repository(Category::class);

        CategoryFactory::new()->createMany(2);

        $object = $repository->first();

        $this->assertInstanceOf(Proxy::class, $object);

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
        $repository = repository(Category::class);
        $proxy = CategoryFactory::new()->create(['name' => 'foo']);

        $this->assertInstanceOf(Proxy::class, $repository->find($proxy));
        $this->assertInstanceOf(Proxy::class, $repository->find($proxy->object()));
        $this->assertInstanceOf(Proxy::class, $repository->find(['name' => 'foo']));
    }

    /**
     * @test
     */
    public function can_find_random_object(): void
    {
        CategoryFactory::new()->createMany(5);

        $ids = [];

        while (5 !== \count(\array_unique($ids))) {
            $ids[] = repository(Category::class)->random()->getId();
        }

        $this->assertCount(5, \array_unique($ids));
    }

    /**
     * @test
     */
    public function at_least_one_object_must_exist_to_get_random_object(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('At least 1 "%s" object(s) must have been persisted (0 persisted).', Category::class));

        repository(Category::class)->random();
    }

    /**
     * @test
     */
    public function can_find_random_set_of_objects(): void
    {
        CategoryFactory::new()->createMany(5);

        $objects = repository(Category::class)->randomSet(3);

        $this->assertCount(3, $objects);
        $this->assertCount(3, \array_unique(\array_map(static function($category) { return $category->getId(); }, $objects)));
    }

    /**
     * @test
     */
    public function random_set_number_must_be_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$number must be positive (-1 given).');

        repository(Category::class)->randomSet(-1);
    }

    /**
     * @test
     */
    public function the_number_of_persisted_objects_must_be_at_least_the_random_set_number(): void
    {
        CategoryFactory::new()->createMany(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('At least 2 "%s" object(s) must have been persisted (1 persisted).', Category::class));

        repository(Category::class)->randomSet(2);
    }

    /**
     * @test
     */
    public function can_find_random_range_of_objects(): void
    {
        CategoryFactory::new()->createMany(5);

        $counts = [];

        while (4 !== \count(\array_unique($counts))) {
            $counts[] = \count(repository(Category::class)->randomRange(0, 3));
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
        CategoryFactory::new()->createMany(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('At least 2 "%s" object(s) must have been persisted (1 persisted).', Category::class));

        repository(Category::class)->randomRange(0, 2);
    }

    /**
     * @test
     */
    public function random_range_min_cannot_be_less_than_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$min must be positive (-1 given).');

        repository(Category::class)->randomRange(-1, 3);
    }

    /**
     * @test
     */
    public function random_set_max_cannot_be_less_than_min(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$max (3) cannot be less than $min (5).');

        repository(Category::class)->randomRange(5, 3);
    }
}
