<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\ChainManagerRegistry;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;

final class ChainManagerRegistryTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_repository(): void
    {
        $managerRegistry1 = $this->createMock(ManagerRegistry::class);
        $managerRegistry2 = $this->createMock(ManagerRegistry::class);

        $managerRegistry2->expects($this->once())->method('getRepository')->willReturn(
            $repository = $this->createMock(ObjectRepository::class)
        );

        $chainManagerRegistry = new ChainManagerRegistry([$managerRegistry1, $managerRegistry2]);

        $this->assertSame($repository, $chainManagerRegistry->getRepository(Post::class));
    }

    /**
     * @test
     */
    public function throws_exception_if_repository_not_found(): void
    {
        $class = Post::class;
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot find repository for class {$class}");

        $managerRegistry1 = $this->createMock(ManagerRegistry::class);
        $managerRegistry2 = $this->createMock(ManagerRegistry::class);

        $chainManagerRegistry = new ChainManagerRegistry([$managerRegistry1, $managerRegistry2]);

        $chainManagerRegistry->getRepository($class);
    }

    /**
     * @test
     */
    public function can_get_manager_from_class(): void
    {
        $managerRegistry1 = $this->createMock(ManagerRegistry::class);
        $managerRegistry2 = $this->createMock(ManagerRegistry::class);

        $managerRegistry2->expects($this->once())->method('getManagerForClass')->willReturn(
            $objectManager = $this->createMock(ObjectManager::class)
        );

        $chainManagerRegistry = new ChainManagerRegistry([$managerRegistry1, $managerRegistry2]);

        $this->assertSame($objectManager, $chainManagerRegistry->getManagerForClass(Post::class));
    }

    /**
     * @test
     */
    public function returns_null_if_no_manager_found(): void
    {
        $managerRegistry1 = $this->createMock(ManagerRegistry::class);
        $managerRegistry2 = $this->createMock(ManagerRegistry::class);

        $chainManagerRegistry = new ChainManagerRegistry([$managerRegistry1, $managerRegistry2]);

        $this->assertNull($chainManagerRegistry->getManagerForClass(Post::class));
    }

    /**
     * @test
     */
    public function can_get_managers(): void
    {
        $managerRegistry1 = $this->createMock(ManagerRegistry::class);
        $managerRegistry2 = $this->createMock(ManagerRegistry::class);

        $managerRegistry1->expects($this->once())->method('getManagers')->willReturn(
            [$objectManager1 = $this->createMock(ObjectManager::class)]
        );

        $managerRegistry2->expects($this->once())->method('getManagers')->willReturn(
            [
                $objectManager2 = $this->createMock(ObjectManager::class),
                $objectManager3 = $this->createMock(ObjectManager::class),
            ]
        );

        $chainManagerRegistry = new ChainManagerRegistry([$managerRegistry1, $managerRegistry2]);

        $this->assertSame(
            [$objectManager1, $objectManager2, $objectManager3],
            $chainManagerRegistry->getManagers()
        );
    }
}
