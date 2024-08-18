<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\InMemory;

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class InMemoryRepositoryRegistry
{
    /**
     * @var array<class-string, GenericInMemoryRepository<object>>
     */
    private array $genericInMemoryRepositories = [];

    public function __construct(
        /** @var ServiceLocator<InMemoryRepository<object>> */
        private readonly ServiceLocator $inMemoryRepositories,
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return InMemoryRepository<T>
     */
    public function get(string $class): InMemoryRepository
    {
        if (!$this->inMemoryRepositories->has($class)) {
            return $this->genericInMemoryRepositories[$class] ??= new GenericInMemoryRepository($class); // @phpstan-ignore return.type
        }

        return $this->inMemoryRepositories->get($class); // @phpstan-ignore return.type
    }
}
