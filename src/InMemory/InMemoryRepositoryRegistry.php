<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\InMemory;

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 * @template T of object
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class InMemoryRepositoryRegistry
{
    /**
     * @var array<class-string<T>, GenericInMemoryRepository<T>>
     */
    private array $genericInMemoryRepositories = [];

    public function __construct(
        /** @var ServiceLocator<InMemoryRepository<T>> */
        private readonly ServiceLocator $inMemoryRepositories,
    ) {
    }

    /**
     * @param class-string<T> $class
     *
     * @return InMemoryRepository<T>
     */
    public function get(string $class): InMemoryRepository
    {
        if (!$this->inMemoryRepositories->has($class)) {
            return $this->genericInMemoryRepositories[$class] ??= new GenericInMemoryRepository($class);
        }

        return $this->inMemoryRepositories->get($class);
    }
}
