<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use function Zenstruck\Foundry\create;
use function Zenstruck\Foundry\create_many;
use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\instantiate;
use function Zenstruck\Foundry\instantiate_many;
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
    public function instantiate(): void
    {
        $proxy = instantiate(Post::class, ['title' => 'title', 'body' => 'body']);

        $this->assertInstanceOf(Post::class, $proxy->object());
        $this->assertFalse($proxy->isPersisted());
        $this->assertSame('title', $proxy->getTitle());
    }

    /**
     * @test
     */
    public function instantiate_many(): void
    {
        $objects = instantiate_many(3, Category::class);

        $this->assertCount(3, $objects);
        $this->assertInstanceOf(Category::class, $objects[0]->object());
        $this->assertFalse($objects[0]->isPersisted());
    }

    /**
     * @test
     */
    public function create(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        Factory::configuration()->setManagerRegistry($registry);

        $object = create(Category::class);

        $this->assertInstanceOf(Proxy::class, $object);
    }

    /**
     * @test
     */
    public function create_many(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectManager::class))
        ;

        Factory::configuration()->setManagerRegistry($registry);

        $objects = create_many(3, Category::class);

        $this->assertCount(3, $objects);
    }

    /**
     * @test
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

        $this->assertInstanceOf(RepositoryProxy::class, repository(new Category()));
    }
}
