<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\PersistenceManager;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\ResetGlobals;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PersistenceManagerTest extends TestCase
{
    use ResetGlobals;

    /**
     * @test
     */
    public function can_get_repository_for_object(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectRepository::class))
        ;

        PersistenceManager::register($registry);

        $this->assertInstanceOf(RepositoryProxy::class, PersistenceManager::repositoryFor(new Category()));
    }

    /**
     * @test
     */
    public function can_get_repository_for_class(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectRepository::class))
        ;

        PersistenceManager::register($registry);

        $this->assertInstanceOf(RepositoryProxy::class, PersistenceManager::repositoryFor(Category::class));
    }

    /**
     * @test
     */
    public function can_get_repository_for_object_proxy(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->createMock(ObjectRepository::class))
        ;

        PersistenceManager::register($registry);

        $proxy = (new Proxy(new Category()))->withoutAutoRefresh();

        $this->assertInstanceOf(RepositoryProxy::class, PersistenceManager::repositoryFor($proxy));
    }

    /**
     * @test
     */
    public function can_persist_object(): void
    {
        $category = new Category();

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())->method('persist')->with($category);
        $manager->expects($this->once())->method('flush');

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager)
        ;

        PersistenceManager::register($registry);

        $object = PersistenceManager::persist($category);

        $this->assertSame($category, $object);
    }

    /**
     * @test
     */
    public function exception_thrown_if_no_manager_registry_registered(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ManagerRegistry not registered...');

        PersistenceManager::objectManagerFor(Category::class);
    }

    /**
     * @test
     */
    public function exception_thrown_if_manager_does_not_manage_object(): void
    {
        PersistenceManager::register($this->createMock(ManagerRegistry::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('No object manager registered for "%s".', Category::class));

        PersistenceManager::objectManagerFor(Category::class);
    }
}
