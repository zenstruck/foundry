<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\ChangesEntityRelationshipCascadePersist;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\DoctrineCascadeRelationshipMetadata;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationships;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;

#[RequiresEnvironmentVariable('DATABASE_URL')]
final class CascadePersistPermutationsTest extends KernelTestCase
{
    use ChangesEntityRelationshipCascadePersist;

    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(Contact::class, ['category'])]
    public function it_applies_the_provided_cascade(DoctrineCascadeRelationshipMetadata ...$metadata): void
    {
        foreach ($metadata as $metadatum) {
            $mapping = self::associationMapping($metadatum->class, $metadatum->field);

            self::assertSame($metadatum->cascade, $mapping->isCascadePersist());
            self::assertSame($metadatum->orphanRemoval, $mapping->orphanRemoval);
        }
    }

    // must stay after the test above: it checks the last permutation does not leak
    #[Test]
    public function it_restores_the_mapping_of_the_fixtures(): void
    {
        $mapping = self::associationMapping(Category::class, 'contacts');

        self::assertFalse($mapping->isCascadePersist());
        self::assertFalse($mapping->orphanRemoval);
    }

    /**
     * @param class-string $class
     */
    private static function associationMapping(string $class, string $field): AssociationMapping
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->getClassMetadata($class)->getAssociationMapping($field);
    }
}
