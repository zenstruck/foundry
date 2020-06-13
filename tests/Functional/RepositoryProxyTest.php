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

        CategoryFactory::new()->persistMany(2);

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

        CategoryFactory::new()->persistMany(2);

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
        $proxy = CategoryFactory::new()->persist(['name' => 'foo']);

        $this->assertInstanceOf(Proxy::class, $repository->find($proxy));
        $this->assertInstanceOf(Proxy::class, $repository->find($proxy->object()));
        $this->assertInstanceOf(Proxy::class, $repository->find(['name' => 'foo']));
    }
}
