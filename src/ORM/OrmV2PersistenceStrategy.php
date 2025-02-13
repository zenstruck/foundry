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
use Zenstruck\Foundry\Persistence\InverseRelationshipMetadata;

/**
 * @internal
 *
 * @phpstan-import-type AssociationMapping from \Doctrine\ORM\Mapping\ClassMetadata
 */
final class OrmV2PersistenceStrategy extends AbstractORMPersistenceStrategy
{
    public function inversedRelationshipMetadata(string $parent, string $child, string $field): ?InverseRelationshipMetadata
    {
        $metadata = $this->classMetadata($child);

        $inversedAssociation = $this->getAssociationMapping($parent, $child, $field);

        if (null === $inversedAssociation || !$metadata instanceof ClassMetadataInfo) {
            return null;
        }

        if (!\is_a(
            $child,
            $inversedAssociation['targetEntity'],
            allow_string: true
        )) { // is_a() handles inheritance as well
            throw new \LogicException("Cannot find correct association named \"{$field}\" between classes [parent: \"{$parent}\", child: \"{$child}\"]");
        }

        // exclude "owning" side of the association (owning OneToOne or ManyToOne)
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

        // only keep *ToOne associations
        if (!$metadata->isSingleValuedAssociation($association['fieldName'])) {
            return null;
        }

        $inversedAssociationMetadata = $this->classMetadata($inversedAssociation['sourceEntity']);

        return new InverseRelationshipMetadata(
            inverseField: $association['fieldName'],
            isCollection: $inversedAssociationMetadata->isCollectionValuedAssociation($inversedAssociation['fieldName']),
            collectionIndexedBy: $inversedAssociation['indexBy'] ?? null
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

        if (!\is_a($targetEntity, $associationMapping['targetEntity'], allow_string: true)) {
            return null;
        }

        return $associationMapping;
    }
}
