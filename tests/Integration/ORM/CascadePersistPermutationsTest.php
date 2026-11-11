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
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationships;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;

#[RequiresEnvironmentVariable('DATABASE_URL')]
final class CascadePersistPermutationsTest extends KernelTestCase
{
    use ChangesEntityRelationshipCascadePersist;

    #[Test]
    #[UsingRelationships(Contact::class, ['category'])]
    public function it_applies_the_cascade_of_the_permutation(): void
    {
        $this->assertCascadeIsApplied();
    }

    #[Test]
    #[UsingRelationships(Contact::class, ['category'])]
    #[DataProvider('provideNames')]
    public function it_crosses_the_permutations_with_a_data_provider(string $name): void
    {
        self::assertContains($name, ['foo', 'bar']);
        $this->assertCascadeIsApplied();
    }

    public static function provideNames(): iterable
    {
        yield 'foo' => ['foo'];
        yield 'bar' => ['bar'];
    }

    // must stay after the tests above: it checks the last permutation does not leak
    #[Test]
    public function it_restores_the_mapping_of_the_fixtures(): void
    {
        $mapping = self::associationMapping(Category::class, 'contacts');

        self::assertFalse($mapping->isCascadePersist());
        self::assertFalse($mapping->orphanRemoval);
    }

    private function assertCascadeIsApplied(): void
    {
        $combination = $this->cascadePersistCombination();
        self::assertNotEmpty($combination);

        foreach ($combination as $metadatum) {
            $mapping = self::associationMapping($metadatum->class, $metadatum->field);

            self::assertSame($metadatum->cascade, $mapping->isCascadePersist());
            self::assertSame($metadatum->orphanRemoval, $mapping->orphanRemoval);
        }
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
