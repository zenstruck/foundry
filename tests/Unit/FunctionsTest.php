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
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;

use function Zenstruck\Foundry\create;
use function Zenstruck\Foundry\create_many;
use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\instantiate_many;
use function Zenstruck\Foundry\lazy;
use function Zenstruck\Foundry\memoize;
use function Zenstruck\Foundry\object;
use function Zenstruck\Foundry\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FunctionsTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    public function faker(): void
    {
        $this->assertIsString(faker()->name());
    }

    /**
     * @test
     */
    public function lazy(): void
    {
        $value = lazy(fn() => new \stdClass());

        $this->assertInstanceOf(LazyValue::class, $value);
        $this->assertNotSame($value(), $value());
    }

    /**
     * @test
     */
    public function memoize(): void
    {
        $value = memoize(fn() => new \stdClass());

        $this->assertInstanceOf(LazyValue::class, $value);
        $this->assertSame($value(), $value());
    }

    /**
     * @test
     */
    public function instantiate(): void
    {
        $proxy = object(Post::class, ['title' => 'title', 'body' => 'body']);

        $this->assertInstanceOf(Post::class, $proxy->_real());
        $this->assertSame('title', $proxy->getTitle());
    }

    /**
     * @test
     * @group legacy
     */
    public function instantiate_many(): void
    {
        $objects = instantiate_many(3, Category::class);

        $this->assertCount(3, $objects);
        $this->assertInstanceOf(Category::class, $objects[0]->_real());
    }

    /**
     * @test
     * @group legacy
     */
    public function create(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        Factory::configuration()->setManagerRegistry($registry)->enableDefaultProxyAutoRefresh();

        $object = create(Category::class);

        $this->assertInstanceOf(Proxy::class, $object);
    }

    /**
     * @test
     * @group legacy
     */
    public function create_many(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        Factory::configuration()->setManagerRegistry($registry)->enableDefaultProxyAutoRefresh();

        $objects = create_many(3, Category::class);

        $this->assertCount(3, $objects);
    }

    /**
     * @test
     * @group legacy
     */
    public function repository(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectRepository::class))
        ;

        Factory::configuration()->setManagerRegistry($registry);

        $this->assertInstanceOf(RepositoryDecorator::class, repository(new Category()));
    }
}
