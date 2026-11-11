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

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * This class changes the "cascade persist" value of a doctrine relationship.
 * @see ChangesEntityRelationshipCascadePersist
 */
#[AsDoctrineListener(event: Events::loadClassMetadata)]
final class ChangeCascadePersistOnLoadClassMetadataListener
{
    /** @var list<DoctrineCascadeRelationshipMetadata> */
    private array $metadata = [];

    /**
     * @param list<DoctrineCascadeRelationshipMetadata> $metadata
     */
    public function withMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata = $eventArgs->getClassMetadata();

        foreach ($this->metadata as $metadatum) {
            if ($classMetadata->getName() === $metadatum->class) {
                // getAssociationMapping() returns a copy with ORM 2
                $mapping = &$classMetadata->associationMappings[$metadatum->field];
                $mapping['cascade'] = $metadatum->cascade ? ['persist'] : [];

                if (\is_array($mapping)) {
                    // ORM 2 reads this flag, computed once when the mapping is validated
                    $mapping['isCascadePersist'] = $metadatum->cascade;
                }

                if ($metadatum->orphanRemoval) {
                    $mapping['orphanRemoval'] = true;
                }

                unset($mapping);
            }
        }
    }
}
