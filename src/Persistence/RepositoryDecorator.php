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

use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Persistence\Exception\NotEnoughObjects;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @implements \IteratorAggregate<array-key, T>
 *
 * @phpstan-import-type Parameters from Factory
 */
class RepositoryDecorator implements \IteratorAggregate, \Countable
{
    /**
     * @internal
     *
     * @param class-string<T> $class
     */
    public function __construct(protected string $class)
    {
    }

    /**
     * @internal
     *
     * @template O of object
     *
     * @param class-string<O> $class
     *
     * @return self<O>
     */
    public static function for(string $class): self
    {
        return Configuration::instance()->persistence()->strategyFor($class) instanceof DoctrinePersistenceStrategy
            ? new DoctrineRepositoryDecorator($class)
            : new self($class);
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
    public function find(mixed $id): ?object
    {
        if (\is_array($id) && (empty($id) || !\array_is_list($id))) {
            /** @var T|null $object */
            $object = $this->findOneBy($id);

            return $object;
        }

        return $this->findByIdentifier($id);
    }

    /**
     * Looking an object up by a bare identifier needs the backend to know its own identity fields.
     *
     * @return T|null
     */
    protected function findByIdentifier(mixed $id): ?object
    {
        throw new \BadMethodCallException(\sprintf('Looking "%s" up by a bare identifier is not supported by its backend. Pass criteria instead, eg. find([\'id\' => $id]).', $this->class));
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
     * @phpstan-param Parameters $criteria
     * @phpstan-param array<string, 'asc'|'desc'|'ASC'|'DESC'>|null $orderBy
     *
     * @return list<T>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $objects = Configuration::instance()->persistence()
            ->strategyFor($this->class)
            ->findBy($this->class, $this->normalize($criteria), $orderBy, $limit, $offset);

        Configuration::instance()->persistedObjectsTracker?->add(...$objects);

        return $objects;
    }

    /**
     * @phpstan-param Parameters $criteria
     *
     * @return T|null
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->findBy($criteria, limit: 1)[0] ?? null;
    }

    /**
     * @return class-string<T>
     */
    public function getClassName(): string
    {
        return $this->class;
    }

    /**
     * @phpstan-param Parameters $criteria
     */
    public function count(array $criteria = []): int
    {
        return \count($this->findBy($criteria));
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
     * @phpstan-param Parameters $criteria
     *
     * @return non-empty-list<T>
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
     * @phpstan-param Parameters $criteria
     *
     * @return list<T>
     * @phpstan-return ($min is positive-int ? non-empty-list<T> : list<T>)
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
        yield from $this->findAll();
    }

    /**
     * @phpstan-param Parameters $criteria
     *
     * @return Parameters
     */
    protected function normalize(array $criteria): array
    {
        $normalized = [];

        foreach ($criteria as $key => $value) {
            if ($value instanceof Factory) {
                // create factories
                $value = $value instanceof PersistentObjectFactory ? $value->withoutPersisting()->create() : $value->create();
            }

            if (!\is_object($value) || null === $embeddableProps = Configuration::instance()->persistence()->embeddablePropertiesFor($value, $this->getClassName())) {
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
