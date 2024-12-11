<?php

declare(strict_types=1);

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
                $classMetadata->getAssociationMapping($metadatum->field)['cascade'] = $metadatum->cascade ? ['persist'] : [];
            }
        }
    }
}
