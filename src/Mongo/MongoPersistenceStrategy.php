<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Mongo;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException as MongoMappingException;
use Doctrine\Persistence\Mapping\MappingException;
use Zenstruck\Foundry\Persistence\PersistenceStrategy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @method DocumentManager       objectManagerFor(string $class)
 * @method list<DocumentManager> objectManagers()
 */
final class MongoPersistenceStrategy extends PersistenceStrategy
{
    public function contains(object $object): bool
    {
        $dm = $this->objectManagerFor($object::class);

        return $dm->contains($object) && !$dm->getUnitOfWork()->isScheduledForInsert($object);
    }

    public function hasChanges(object $object): bool
    {
        $dm = $this->objectManagerFor($object::class);

        if (!$dm->contains($object)) {
            return false;
        }

        // cannot use UOW::recomputeSingleEntityChangeSet() here as it wrongly computes embedded objects as changed
        $dm->getUnitOfWork()->computeChangeSet($dm->getClassMetadata($object::class), $object);

        return (bool) $dm->getUnitOfWork()->getDocumentChangeSet($object);
    }

    public function truncate(string $class): void
    {
        $this->objectManagerFor($class)->getDocumentCollection($class)->deleteMany([]);
    }

    public function managedNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->objectManagers() as $objectManager) {
            $namespaces[] = $objectManager->getConfiguration()->getDocumentNamespaces();
        }

        return \array_values(\array_merge(...$namespaces));
    }

    public function embeddablePropertiesFor(object $object, string $owner): ?array
    {
        try {
            $metadata = $this->objectManagerFor($owner)->getClassMetadata($object::class);
        } catch (MappingException|MongoMappingException) {
            return null;
        }

        if (!$metadata->isEmbeddedDocument) {
            return null;
        }

        $properties = [];

        foreach ($metadata->getFieldNames() as $field) {
            $properties[$field] = $metadata->getFieldValue($object, $field);
        }

        return $properties;
    }

    public function isEmbeddable(object $object): bool
    {
        return $this->objectManagerFor($object::class)->getClassMetadata($object::class)->isEmbeddedDocument;
    }

    public function isScheduledForInsert(object $object): bool
    {
        $uow = $this->objectManagerFor($object::class)->getUnitOfWork();

        return $uow->isScheduledForInsert($object) || $uow->isScheduledForUpsert($object);
    }

    public function findBy(string $class, array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->objectManagerFor($class)
            ->getRepository($class)
            ->createQueryBuilder()
            ->refresh();

        foreach ($criteria as $field => $value) {
            $qb->field($field)->equals($value);
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->sort($field, $direction);
            }
        }

        if ($limit) {
            $qb->limit($limit);
        }

        if ($offset) {
            $qb->skip($offset);
        }

        return $qb->getQuery()->execute()->toArray(); // @phpstan-ignore method.nonObject
    }
}
