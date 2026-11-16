<?php

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

    private bool $enabled = false;

    public function __construct(
        /** @var ServiceLocator<InMemoryRepository<object>> */
        private readonly ServiceLocator $inMemoryRepositories,
    ) {
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Read on every strategy lookup: the in-memory strategy shadows all the others, and getting
     * the flag through Configuration::instance() would cost a container lookup each time.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
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
