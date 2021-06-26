<?php

namespace Zenstruck\Foundry\Tests\Unit\Proxy;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Proxy\ProxyGenerator;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Tag;

class ProxyGeneratorTest extends TestCase
{
    /** @var EntityManagerInterface|MockObject */
    private $objectManager;
    /** @var ManagerRegistry|MockObject */
    private $managerRegistry;
    /** @var Configuration|MockObject */
    private $config;
    /** @var ProxyGenerator */
    private $generator;

    protected function setUp(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->any())->method('getEntityChangeSet')->willReturn([]);

        $this->objectManager = $this->createMock(EntityManagerInterface::class);
        $this->objectManager->expects($this->any())->method('contains')->willReturn(true);
        $this->objectManager->expects($this->any())->method('getClassMetadata')->willReturn($this->createMock(ClassMetadata::class));
        $this->objectManager->expects($this->any())->method('getUnitOfWork')->willReturn($unitOfWork);

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->config = new Configuration();
        $this->config->setManagerRegistry($this->managerRegistry);
        $this->generator = new ProxyGenerator($this->config);
    }

    /** @test */
    public function can_auto_refresh_all_methods(): void
    {
        $tag = new Tag();
        $this->managerRegistry->expects($this->any())->method('getManagerForClass')->with(Tag::class)->willReturn($this->objectManager);
        $proxyTag = $this->generator->generate($tag);

        $this->objectManager->expects($this->exactly(2))->method('refresh')->with($tag);
        $proxyTag->setName('proxy');
        $this->assertEquals('proxy', $proxyTag->getName());
    }

    /** @test */
    public function can_only_auto_refresh_getters(): void
    {
        $tag = new Tag();
        $this->managerRegistry->expects($this->any())->method('getManagerForClass')->with(Tag::class)->willReturn($this->objectManager);
        $proxyTag = $this->generator->generate($tag, ['get*']);

        $this->objectManager->expects($this->once())->method('refresh')->with($tag);
        $proxyTag->setName('proxy');
        $this->assertEquals('proxy', $proxyTag->getName());
    }
}
