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
final class ProxyRepositoryDecorator extends RepositoryDecorator
{
    /**
     * @return T|Proxy<T>|null
     * @psalm-return (T&Proxy<T>)|null
     */
    public function first(string $sortBy = 'id'): ?object
    {
        return $this->proxyNullableObject(parent::first($sortBy));
    }

    /**
     * @return T|Proxy<T>
     * @psalm-return T&Proxy<T>
     */
    public function firstOrFail(string $sortBy = 'id'): object
    {
        return proxy(parent::firstOrFail($sortBy));
    }

    /**
     * @return T|Proxy<T>|null
     * @psalm-return (T&Proxy<T>)|null
     */
    public function last(string $sortBy = 'id'): ?object
    {
        return $this->proxyNullableObject(parent::last($sortBy));
    }

    /**
     * @return T|Proxy<T>
     * @psalm-return T&Proxy<T>
     */
    public function lastOrFail(string $sortBy = 'id'): object
    {
        return proxy(parent::lastOrFail($sortBy));
    }

    /**
     * @return T|Proxy<T>|null
     * @psalm-return (T&Proxy<T>)|null
     */
    public function find($id): ?object
    {
        return $this->proxyNullableObject(parent::find($id));
    }

    /**
     * @return T|Proxy<T>
     * @psalm-return T&Proxy<T>
     */
    public function findOrFail(mixed $id): object
    {
        return proxy(parent::findOrFail($id));
    }

    /**
     * @phpstan-return list<T&Proxy<T>>
     * @psalm-return list<T&Proxy<T>>
     */
    public function findAll(): array
    {
        return $this->proxyArray(parent::findAll());
    }

    /**
     * @psalm-return list<T&Proxy<T>>
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->proxyArray(parent::findBy($criteria, $orderBy, $limit, $offset));
    }

    /**
     * @return T|Proxy<T>|null
     * @psalm-return (T&Proxy<T>)|null
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->proxyNullableObject(parent::findOneBy($criteria));
    }

    /**
     * @return T|Proxy<T>|null
     * @psalm-return T&Proxy<T>
     */
    public function random(array $criteria = []): object
    {
        return proxy(parent::random($criteria));
    }

    /**
     * @psalm-return list<T&Proxy<T>>
     */
    public function randomSet(int $count, array $criteria = []): array
    {
        return $this->proxyArray(
            parent::randomSet($count, $criteria)
        );
    }

    /**
     * @psalm-return list<T&Proxy<T>>
     */
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
     * @param  list<T>          $objects
     * @return list<T&Proxy<T>>
     */
    private function proxyArray(array $objects): array
    {
        return \array_map(
            static fn(object $object) => proxy($object),
            $objects
        );
    }

    /**
     * @param  T|null            $object
     * @return (T&Proxy<T>)|null
     */
    private function proxyNullableObject(?object $object): ?object
    {
        if (null === $object) {
            return null;
        }

        return proxy($object);
    }
}
