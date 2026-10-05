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

use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\CustomPersistenceStrategy;
use Zenstruck\Foundry\Persistence\PersistenceStrategy;
use Zenstruck\Foundry\Persistence\Relationship\RelationshipMetadata;

use function Zenstruck\Foundry\get;

/**
 * Serves every class while the in-memory mode is on, so it is registered with a higher priority than
 * the Doctrine strategies and shadows them.
 *
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class InMemoryPersistenceStrategy extends CustomPersistenceStrategy
{
    /**
     * @param iterable<PersistenceStrategy> $decorated the strategies this one shadows; tagged_iterator
     *                                                 excludes the current service, so never itself
     */
    public function __construct(
        private readonly InMemoryRepositoryRegistry $registry,
        private readonly iterable $decorated = [],
    ) {
    }

    public function supports(string $class): bool
    {
        return Configuration::instance()->isInMemoryEnabled();
    }

    public function persist(object $object): void
    {
        $this->registry->get($object::class)->_save($object);
    }

    public function findBy(string $class, array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $results = \array_values(
            \array_filter(
                $this->registry->get($class)->_all(),
                static fn(object $o) => \array_all($criteria, static fn(mixed $criterion, string $key) => get($o, $key) === $criterion),
            )
        );

        if ($orderBy) {
            if (\count($orderBy) > 1) {
                throw new \InvalidArgumentException('Order by multiple fields is not supported.');
            }

            $field = \array_key_first($orderBy);

            'asc' === \mb_strtolower($orderBy[$field])
                ? \usort($results, static fn(object $a, object $b) => get($a, $field) <=> get($b, $field))
                : \usort($results, static fn(object $a, object $b) => get($b, $field) <=> get($a, $field));
        }

        return \array_slice($results, $offset ?? 0, $limit);
    }

    /**
     * Objects held in memory have no identity, so find() by id is not supported -- findBy() is.
     */
    protected function identifierFields(string $class): array
    {
        return [];
    }

    /**
     * In-memory replaces persistence, not mapping: relations between Doctrine entities must keep
     * being wired, so the question goes to the strategy that owns the mapping.
     */
    public function bidirectionalRelationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        return $this->mappingFor($parent)?->bidirectionalRelationshipMetadata($parent, $child, $field);
    }

    /**
     * @param class-string $class
     */
    private function mappingFor(string $class): ?PersistenceStrategy
    {
        return \array_find(
            \is_array($this->decorated) ? $this->decorated : \iterator_to_array($this->decorated),
            static fn(PersistenceStrategy $strategy) => $strategy->supports($class),
        );
    }
}
