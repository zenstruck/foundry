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
use Zenstruck\Foundry\Persistence\RelationshipMetadata;

/**
 * @internal
 *
 * @phpstan-import-type AssociationMapping from \Doctrine\ORM\Mapping\ClassMetadata
 */
final class OrmV2PersistenceStrategy extends AbstractORMPersistenceStrategy
{
    public function relationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        $metadata = $this->classMetadata($parent);

        $association = $this->getAssociationMapping($parent, $child, $field);

        if ($association) {
            return new RelationshipMetadata(
                isCascadePersist: $association['isCascadePersist'],
                inverseField: $metadata->isSingleValuedAssociation($association['fieldName']) ? $association['fieldName'] : null,
                isCollection: $metadata->isCollectionValuedAssociation($association['fieldName']),
                isOneToOne: $association['type'] === ClassMetadataInfo::ONE_TO_ONE
            );
        }

        $inversedAssociation = $this->getAssociationMapping($child, $parent, $field);

        if (null === $inversedAssociation || !$metadata instanceof ClassMetadataInfo) {
            return null;
        }

        if (!\is_a(
            $parent,
            $inversedAssociation['targetEntity'],
            allow_string: true
        )) { // is_a() handles inheritance as well
            throw new \LogicException("Cannot find correct association named \"{$field}\" between classes [parent: \"{$parent}\", child: \"{$child}\"]");
        }

        if (!\in_array(
            $inversedAssociation['type'],
            [ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::ONE_TO_ONE],
            true
        )
            || !isset($inversedAssociation['mappedBy'])
        ) {
            return null;
        }

        $association = $metadata->getAssociationMapping($inversedAssociation['mappedBy']);
        $inversedAssociationMetadata = $this->classMetadata($inversedAssociation['sourceEntity']);

        return new RelationshipMetadata(
            isCascadePersist: $inversedAssociation['isCascadePersist'],
            inverseField: $metadata->isSingleValuedAssociation($association['fieldName']) ? $association['fieldName'] : null,
            isCollection: $inversedAssociationMetadata->isCollectionValuedAssociation($inversedAssociation['fieldName']),
            isOneToOne: $inversedAssociation['type'] === ClassMetadataInfo::ONE_TO_ONE
        );
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

        if (!is_a($targetEntity, $associationMapping['targetEntity'], allow_string: true)) {
            return null;
        }

        return $associationMapping;
    }
}
