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

use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\Mapping\OneToOneAssociationMapping;
use Doctrine\Persistence\Mapping\MappingException;
use Zenstruck\Foundry\Persistence\Relationship\ManyToOneRelationship;
use Zenstruck\Foundry\Persistence\Relationship\OneToManyRelationship;
use Zenstruck\Foundry\Persistence\Relationship\OneToOneRelationship;
use Zenstruck\Foundry\Persistence\Relationship\RelationshipMetadata;

final class OrmV3PersistenceStrategy extends AbstractORMPersistenceStrategy
{
    public function bidirectionalRelationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        $associationMapping = $this->getAssociationMapping($parent, $child, $field);

        if (null === $associationMapping) {
            return null;
        }

        if (!\is_a(
            $child,
            $associationMapping->targetEntity,
            allow_string: true
        )) { // is_a() handles inheritance as well
            throw new \LogicException("Cannot find correct association named \"{$field}\" between classes [parent: \"{$parent}\", child: \"{$child}\"]");
        }

        $inverseField = $associationMapping->isOwningSide() ? $associationMapping->inversedBy : $associationMapping->mappedBy;

        if (null === $inverseField) {
            return null;
        }

        return match (true) {
            $associationMapping instanceof OneToManyAssociationMapping => new OneToManyRelationship(
                inverseField: $inverseField,
                collectionIndexedBy: $associationMapping->isIndexed() ? $associationMapping->indexBy() : null
            ),
            $associationMapping instanceof OneToOneAssociationMapping => new OneToOneRelationship(
                inverseField: $inverseField,
                isOwning: $associationMapping->isOwningSide()
            ),
            $associationMapping instanceof ManyToOneAssociationMapping => new ManyToOneRelationship(
                inverseField: $inverseField,
            ),
            default => null,
        };
    }

    /**
     * @param class-string $entityClass
     */
    private function getAssociationMapping(string $entityClass, string $targetEntity, string $field): ?AssociationMapping
    {
        try {
            $associationMapping = $this->objectManagerFor($entityClass)->getClassMetadata($entityClass)->getAssociationMapping($field);
        } catch (MappingException|ORMMappingException) {
            return null;
        }

        if (!\is_a($targetEntity, $associationMapping->targetEntity, allow_string: true)) {
            return null;
        }

        return $associationMapping;
    }
}
