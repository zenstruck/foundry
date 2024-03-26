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

        $association = $this->getAssociationMapping($parent, $field);

        if (null === $association) {
            $inversedAssociation = $this->getAssociationMapping($child, $field);

            if (null === $inversedAssociation || !$metadata instanceof ClassMetadataInfo) {
                return null;
            }

            if (!\is_a($parent, $inversedAssociation['targetEntity'], allow_string: true)) { // is_a() handles inheritance as well
                throw new \LogicException("Cannot find correct association named \"{$field}\" between classes [parent: \"{$parent}\", child: \"{$child}\"]");
            }

            if (ClassMetadataInfo::ONE_TO_MANY !== $inversedAssociation['type'] || !isset($inversedAssociation['mappedBy'])) {
                return null;
            }

            $association = $metadata->getAssociationMapping($inversedAssociation['mappedBy']);
        }

        return new RelationshipMetadata(
            isCascadePersist: $association['isCascadePersist'],
            inverseField: $metadata->isSingleValuedAssociation($association['fieldName']) ? $association['fieldName'] : null,
        );
    }

    /**
     * @param  class-string $entityClass
     * @return array[]|null
     * @phpstan-return AssociationMapping|null
     */
    private function getAssociationMapping(string $entityClass, string $field): ?array
    {
        try {
            return $this->objectManagerFor($entityClass)->getClassMetadata($entityClass)->getAssociationMapping($field);
        } catch (MappingException|ORMMappingException) {
            return null;
        }
    }
}
