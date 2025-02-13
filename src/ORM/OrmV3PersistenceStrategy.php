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
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\InverseSideMapping;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\ORM\Mapping\ToManyAssociationMapping;
use Doctrine\Persistence\Mapping\MappingException;
use Zenstruck\Foundry\Persistence\InverseRelationshipMetadata;

final class OrmV3PersistenceStrategy extends AbstractORMPersistenceStrategy
{
    public function inversedRelationshipMetadata(string $parent, string $child, string $field): ?InverseRelationshipMetadata
    {
        $metadata = $this->classMetadata($child);

        $inversedAssociation = $this->getAssociationMapping($parent, $child, $field);

        if (null === $inversedAssociation || !$metadata instanceof ClassMetadata) {
            return null;
        }

        if (!\is_a(
            $child,
            $inversedAssociation->targetEntity,
            allow_string: true
        )) { // is_a() handles inheritance as well
            throw new \LogicException("Cannot find correct association named \"{$field}\" between classes [parent: \"{$parent}\", child: \"{$child}\"]");
        }

        // exclude "owning" side of the association (owning OneToOne or ManyToOne)
        if (!$inversedAssociation instanceof InverseSideMapping) {
            return null;
        }

        $association = $metadata->getAssociationMapping($inversedAssociation->mappedBy);

        // only keep *ToOne associations
        if (!$metadata->isSingleValuedAssociation($association->fieldName)) {
            return null;
        }

        return new InverseRelationshipMetadata(
            inverseField: $association->fieldName,
            isCollection: $inversedAssociation instanceof ToManyAssociationMapping,
            collectionIndexedBy: $inversedAssociation->isIndexed() ? $inversedAssociation->indexBy() : null
        );
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
