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

use Doctrine\Persistence\ObjectRepository;
use Zenstruck\Foundry\Configuration;

use function Zenstruck\Foundry\get;

/**
 * @template T of object
 * @implements ObjectRepository<T>
 */
final class InMemoryDoctrineObjectRepositoryAdapter implements ObjectRepository
{
    /** @var InMemoryRepository<T> */
    private InMemoryRepository $innerInMemoryRepo;

    /**
     * @internal
     *
     * @param class-string<T> $class
     */
    public function __construct(private string $class)
    {
        if (!Configuration::instance()->isInMemoryEnabled()) {
            throw new \LogicException('In-memory repositories are not enabled.');
        }

        $this->innerInMemoryRepo = Configuration::instance()->inMemoryRepositoryRegistry->get($this->class);
    }

    public function find(mixed $id): ?object
    {
        throw new \BadMethodCallException('find() is not supported in in-memory repositories. Use findBy() instead.');
    }

    public function findAll(): array
    {
        return $this->innerInMemoryRepo->_all();
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $results = \array_filter(
            $this->innerInMemoryRepo->_all(),
            static function($o) use ($criteria) {
                foreach ($criteria as $key => $criterion) {
                    if (get($o, $key) !== $criterion) {
                        return false;
                    }
                }

                return true;
            }
        );

        $results = \array_values($results);

        if ($orderBy) {
            if (\count($orderBy) > 1) {
                throw new \InvalidArgumentException('Order by multiple fields is not supported.');
            }

            $field = \array_key_first($orderBy);
            $direction = $orderBy[$field];

            if ('asc' === \mb_strtolower($direction)) {
                \usort($results, static fn($a, $b) => get($a, $field) <=> get($b, $field));
            } else {
                \usort($results, static fn($a, $b) => get($b, $field) <=> get($a, $field));
            }
        }

        if (null !== $offset) {
            $results = \array_slice($results, $offset);
        }

        if (null !== $limit) {
            $results = \array_slice($results, 0, $limit);
        }

        return $results;
    }

    public function findOneBy(array $criteria): ?object
    {
        return $this->findBy($criteria, limit: 1)[0] ?? null;
    }

    public function getClassName(): string
    {
        return $this->class;
    }
}
