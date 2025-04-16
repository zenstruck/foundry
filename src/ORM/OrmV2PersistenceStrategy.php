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

namespace Zenstruck\Foundry\ORM;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Persistence\Mapping\MappingException;
use Zenstruck\Foundry\Persistence\Relationship\ManyToOneRelationship;
use Zenstruck\Foundry\Persistence\Relationship\OneToManyRelationship;
use Zenstruck\Foundry\Persistence\Relationship\OneToOneRelationship;
use Zenstruck\Foundry\Persistence\Relationship\RelationshipMetadata;

/**
 * @internal
 *
 * @phpstan-import-type AssociationMapping from \Doctrine\ORM\Mapping\ClassMetadata
 */
final class OrmV2PersistenceStrategy extends AbstractORMPersistenceStrategy
{
    public function bidirectionalRelationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        $associationMapping = $this->getAssociationMapping($parent, $child, $field);

        if (null === $associationMapping) {
            return null;
        }

        if (!\is_a(
            $child,
            $associationMapping['targetEntity'],
            allow_string: true
        )) { // is_a() handles inheritance as well
            throw new \LogicException("Cannot find correct association named \"{$field}\" between classes [parent: \"{$parent}\", child: \"{$child}\"]");
        }

        $inverseField = $associationMapping['isOwningSide'] ? $associationMapping['inversedBy'] ?? null : $associationMapping['mappedBy'] ?? null;

        if (null === $inverseField) {
            return null;
        }

        return match (true) {
            ClassMetadataInfo::ONE_TO_MANY === $associationMapping['type'] => new OneToManyRelationship(
                inverseField: $inverseField,
                collectionIndexedBy: $associationMapping['indexBy'] ?? null
            ),
            ClassMetadataInfo::ONE_TO_ONE === $associationMapping['type'] => new OneToOneRelationship(
                inverseField: $inverseField,
                isOwning: $associationMapping['isOwningSide'] ?? false
            ),
            ClassMetadataInfo::MANY_TO_ONE === $associationMapping['type'] => new ManyToOneRelationship(
                inverseField: $inverseField,
            ),
            default => null,
        };
    }

    /**
     * @param  class-string $entityClass
     * @return array[]|null
     * @phpstan-return AssociationMapping|null
     */
    private function getAssociationMapping(string $entityClass, string $targetEntity, string $field): ?array
    {
        try {
            $associationMapping = $this->objectManagerFor($entityClass)->getClassMetadata($entityClass)->getAssociationMapping($field);
        } catch (MappingException|ORMMappingException) {
            return null;
        }

        if (!\is_a($targetEntity, $associationMapping['targetEntity'], allow_string: true)) {
            return null;
        }

        return $associationMapping;
    }
}
