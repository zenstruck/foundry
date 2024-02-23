<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Doctrine\Persistence\ObjectRepository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @template I of ObjectRepository
 * @extends  RepositoryDecorator<T&Proxy<T>, I>
 */
final class ProxyRepositoryDecorator extends RepositoryDecorator // @phpstan-ignore-line
{
    public function first(string $sortBy = 'id'): ?object
    {
        return $this->proxyNullableObject(parent::first($sortBy));
    }

    public function firstOrFail(string $sortBy = 'id'): object
    {
        return proxy(parent::firstOrFail($sortBy));
    }

    public function last(string $sortedField = 'id'): ?object
    {
        return $this->proxyNullableObject(parent::last($sortedField));
    }

    public function lastOrFail(string $sortBy = 'id'): object
    {
        return proxy(parent::lastOrFail($sortBy));
    }

    public function find($id): ?object
    {
        return $this->proxyNullableObject(parent::find($id));
    }

    public function findOrFail(mixed $id): object
    {
        return proxy(parent::findOrFail($id));
    }

    public function findAll(): array
    {
        return $this->proxyArray(parent::findAll());
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->proxyArray(parent::findBy($criteria, $orderBy, $limit, $offset));
    }

    public function findOneBy(array $criteria): ?object
    {
        return $this->proxyNullableObject(parent::findOneBy($criteria));
    }

    public function random(array $criteria = []): object
    {
        return proxy(parent::random($criteria));
    }

    public function randomSet(int $count, array $criteria = []): array
    {
        return $this->proxyArray(
            parent::randomSet($count, $criteria)
        );
    }

    public function randomRange(int $min, int $max, array $criteria = []): array
    {
        return $this->proxyArray(
            parent::randomRange($min, $max, $criteria)
        );
    }

    public function getIterator(): \Traversable
    {
        foreach (parent::getIterator() as $item) {
            yield proxy($item);
        }
    }

    public function count(array $criteria = []): int
    {
        return parent::count($criteria);
    }

    public function getClassName(): string
    {
        return parent::getClassName();
    }

    /**
     * @param array<T> $objects
     * @return array<T&Proxy<T>>
     */
    private function proxyArray(array $objects): array
    {
        return array_map(
            static fn(object $object) => proxy($object),
            $objects
        );
    }

    /**
     * @param T|null $object
     * @return (T&Proxy<T>)|null
     */
    private function proxyNullableObject(object|null $object): object|null
    {
        if (null === $object) {
            return null;
        }

        return proxy($object);
    }
}
