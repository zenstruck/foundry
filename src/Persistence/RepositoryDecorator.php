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

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\InMemory\InMemoryDoctrineObjectRepositoryAdapter;
use Zenstruck\Foundry\Persistence\Exception\NotEnoughObjects;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @template I of ObjectRepository
 * @implements I<T>
 * @implements \IteratorAggregate<array-key, T>
 * @mixin I
 *
 * @phpstan-import-type Parameters from Factory
 */
class RepositoryDecorator implements ObjectRepository, \IteratorAggregate, \Countable
{
    /**
     * @internal
     *
     * @param class-string<T> $class
     */
    public function __construct(private string $class, private bool $inMemory = false)
    {
    }

    /**
     * @param mixed[] $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->inner()->{$name}(...$arguments);
    }

    public function assert(): RepositoryAssertions
    {
        return new RepositoryAssertions($this);
    }

    /**
     * @return T|null
     */
    public function first(string $sortBy = 'id'): ?object
    {
        return $this->findBy([], [$sortBy => 'ASC'], 1)[0] ?? null;
    }

    /**
     * @return T
     */
    public function firstOrFail(string $sortBy = 'id'): object
    {
        return $this->first($sortBy) ?? throw new \RuntimeException(\sprintf('No "%s" objects persisted.', $this->class));
    }

    /**
     * @return T|null
     */
    public function last(string $sortBy = 'id'): ?object
    {
        return $this->findBy([], [$sortBy => 'DESC'], 1)[0] ?? null;
    }

    /**
     * @return T
     */
    public function lastOrFail(string $sortBy = 'id'): object
    {
        return $this->last($sortBy) ?? throw new \RuntimeException(\sprintf('No "%s" objects persisted.', $this->class));
    }

    /**
     * @return T|null
     */
    public function find($id): ?object
    {
        if (\is_array($id) && (empty($id) || !\array_is_list($id))) {
            /** @var T|null $object */
            $object = $this->findOneBy($id);

            return $object;
        }

        /** @var T|null $object */
        $object = $this->inner()->find(ProxyGenerator::unwrap($id));

        if ($object && !$this instanceof ProxyRepositoryDecorator) {
            Configuration::instance()->persistedObjectsTracker?->add($object);
        }

        return $object;
    }

    /**
     * @return T
     */
    public function findOrFail(mixed $id): object
    {
        return $this->find($id) ?? throw new \RuntimeException(\sprintf('No "%s" object found for "%s".', $this->class, \get_debug_type($id)));
    }

    /**
     * @return list<T>
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    /**
     * @param array<string, string>|null $orderBy
     * @param ?int                       $limit
     * @param ?int                       $offset
     * @phpstan-param array<string, 'asc'|'desc'|'ASC'|'DESC'>|null $orderBy
     *
     * @return list<T>
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        $objects = \array_values($this->inner()->findBy($this->normalize($criteria), $orderBy, $limit, $offset));

        if (!$this instanceof ProxyRepositoryDecorator) {
            Configuration::instance()->persistedObjectsTracker?->add(...$objects);
        }

        return $objects;
    }

    /**
     * @return T|null
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->findBy($criteria, limit: 1)[0] ?? null;
    }

    public function getClassName(): string
    {
        return $this->class;
    }

    /**
     * @phpstan-param Parameters $criteria
     */
    public function count(array $criteria = []): int
    {
        $inner = $this->inner();

        if ($inner instanceof EntityRepository) {
            // use query to avoid loading all entities
            return $inner->count($this->normalize($criteria));
        }

        return \count($this->inner()->findBy($criteria));
    }

    public function truncate(): void
    {
        Configuration::instance()->persistence()->truncate($this->class);
    }

    /**
     * @phpstan-param Parameters $criteria
     *
     * @return T
     */
    public function random(array $criteria = []): object
    {
        $count = $this->count($criteria);
        $offset = 0;

        if (0 === $count) {
            throw new NotEnoughObjects(\sprintf('At least %d "%s" object(s) must have been persisted (%d persisted).', 1, $this->getClassName(), 0));
        }

        if ($count > 1) {
            $offset = \random_int(0, $count - 1);
        }

        $result = $this->findBy($criteria, limit: 1, offset: $offset);

        if (!\count($result)) {
            throw new NotEnoughObjects(\sprintf('At least %d "%s" object(s) must have been persisted (%d persisted).', 1, $this->getClassName(), 0));
        }

        return $result[0];
    }

    /**
     * @param positive-int $count
     * @phpstan-param Parameters   $criteria
     *
     * @return list<T>
     */
    public function randomSet(int $count, array $criteria = []): array
    {
        if ($count < 1) {
            throw new \InvalidArgumentException(\sprintf('$number must be positive (%d given).', $count));
        }

        return $this->randomRange($count, $count, $criteria);
    }

    /**
     * @param int<0, max> $min
     * @param int<0, max> $max
     * @phpstan-param Parameters  $criteria
     *
     * @return list<T>
     */
    public function randomRange(int $min, int $max, array $criteria = []): array
    {
        if ($min < 0) {
            throw new \InvalidArgumentException(\sprintf('$min must be positive (%d given).', $min));
        }

        if ($max < $min) {
            throw new \InvalidArgumentException(\sprintf('$max (%d) cannot be less than $min (%d).', $max, $min));
        }

        $all = \array_values($this->findBy($criteria));

        \shuffle($all);

        if (\count($all) < $max) {
            throw new NotEnoughObjects(\sprintf('At least %d "%s" object(s) must have been persisted (%d persisted).', $max, $this->getClassName(), \count($all)));
        }

        return \array_slice($all, 0, \mt_rand($min, $max));
    }

    public function getIterator(): \Traversable
    {
        if (\is_iterable($this->inner())) {
            return yield from $this->inner();
        }

        yield from $this->findAll();
    }

    /**
     * @return ObjectRepository<T>
     */
    public function inner(): ObjectRepository
    {
        if ($this->inMemory) {
            return new InMemoryDoctrineObjectRepositoryAdapter($this->class);
        }

        return Configuration::instance()->persistence()->repositoryFor($this->class);
    }

    /**
     * @phpstan-param Parameters $criteria
     *
     * @return Parameters
     */
    private function normalize(array $criteria): array
    {
        $normalized = [];

        foreach ($criteria as $key => $value) {
            if ($value instanceof Factory) {
                // create factories
                $value = $value instanceof PersistentObjectFactory ? $value->withoutPersisting()->create() : $value->create();
            }

            if ($value instanceof Proxy) {
                // unwrap proxies
                $value = $value->_real();
            }

            if (!\is_object($value) || null === $embeddableProps = Configuration::instance()->persistence()->embeddablePropertiesFor($value, $this->getClassName())) {
                $normalized[$key] = $value;

                continue;
            }

            if ($this->inMemory) {
                // embeddables should not be expanded in memory
                $normalized[$key] = $value;

                continue;
            }

            // expand embeddables
            foreach ($embeddableProps as $subKey => $subValue) {
                $normalized["{$key}.{$subKey}"] = $subValue;
            }
        }

        return $normalized;
    }
}
