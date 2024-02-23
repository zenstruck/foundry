<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

use function Zenstruck\Foundry\Persistence\repository;
use function Zenstruck\Foundry\Persistence\unproxy;

abstract class GenericRepositoryDecoratorTestCase extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     */
    public function repository_proxy_is_countable_and_iterable(): void
    {
        $this->factory()->many(4)->create();

        $repository = repository($this->modelClass());

        $this->assertCount(4, $repository);
        $this->assertCount(4, \iterator_to_array($repository));
    }

    /**
     * @test
     */
    public function can_fetch_objects(): void
    {
        $this->factory()->many(2)->create();

        $repository = repository($this->modelClass());

        $objects = $repository->findAll();
        $this->assertCount(2, $objects);
        $this->assertInstanceOf($this->modelClass(), $objects[0]);

        $objects = $repository->findBy([]);
        $this->assertCount(2, $objects);
        $this->assertInstanceOf($this->modelClass(), $objects[0]);
    }

    /**
     * @test
     */
    public function can_call_find_with_empty_array(): void
    {
        $object = $this->factory()->create();

        $repository = repository($this->modelClass());

        $this->assertSame(unproxy($object), unproxy($repository->find([])));
    }

    /**
     * @return class-string<GenericModel>
     */
    protected function modelClass(): string
    {
        return $this->factory()::class();
    }

    /**
     * @return PersistentObjectFactory<GenericModel>
     */
    abstract protected function factory(): PersistentObjectFactory;
}
