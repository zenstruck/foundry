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

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\ChangesEntityRelationshipCascadePersist;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationships;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\IndexedOneToMany;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InversedOneToOneWithNonNullableOwning;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InversedOneToOneWithOneToMany;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InversedOneToOneWithSetter;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\ManyToOneToSelfReferencing;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\OneToManyWithUnionType;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RichDomainMandatoryRelationship;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\EdgeCases\MultipleMandatoryRelationshipToSameEntity;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class EdgeCasesRelationshipTest extends KernelTestCase
{
    use ChangesEntityRelationshipCascadePersist, Factories, RequiresORM, ResetDatabase;

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(RichDomainMandatoryRelationship\OwningSide::class, ['main'])]
    #[RequiresPhpunit('>=11.4')]
    public function inversed_relationship_mandatory(): void
    {
        $owningSideEntityFactory = persistent_factory(RichDomainMandatoryRelationship\OwningSide::class);
        $inversedSideEntityFactory = persistent_factory(RichDomainMandatoryRelationship\InversedSide::class);

        $inversedSideEntity = $inversedSideEntityFactory->create([
            'relations' => $owningSideEntityFactory->many(2),
        ]);

        $this->assertCount(2, $inversedSideEntity->getRelations());
        $owningSideEntityFactory::assert()->count(2);
        $inversedSideEntityFactory::assert()->count(1);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(InversedOneToOneWithNonNullableOwning\OwningSide::class, ['inverseSide'])]
    #[RequiresPhpunit('>=11.4')]
    public function inverse_one_to_one_with_non_nullable_inverse_side(): void
    {
        $owningSideFactory = persistent_factory(InversedOneToOneWithNonNullableOwning\OwningSide::class);
        $inverseSideFactory = persistent_factory(InversedOneToOneWithNonNullableOwning\InverseSide::class);

        $inverseSide = $inverseSideFactory->create(['owningSide' => $owningSideFactory]);

        $owningSideFactory::assert()->count(1);
        $inverseSideFactory::assert()->count(1);

        self::assertSame($inverseSide, $inverseSide->owningSide->inverseSide);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(InversedOneToOneWithSetter\OwningSide::class, ['inverseSide'])]
    #[RequiresPhpunit('>=11.4')]
    public function inverse_one_to_one_with_both_nullable(): void
    {
        $owningSideFactory = persistent_factory(InversedOneToOneWithSetter\OwningSide::class);
        $inverseSideFactory = persistent_factory(InversedOneToOneWithSetter\InverseSide::class);

        $inverseSide = $inverseSideFactory->create(['owningSide' => $owningSideFactory]);

        $owningSideFactory::assert()->count(1);
        $inverseSideFactory::assert()->count(1);

        self::assertSame($inverseSide, $inverseSide->getOwningSide()?->inverseSide);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(InversedOneToOneWithOneToMany\OwningSide::class, ['inverseSide'])]
    #[UsingRelationships(InversedOneToOneWithOneToMany\Item::class, ['owningSide'])]
    #[RequiresPhpunit('^11.4')]
    public function inverse_one_to_one_with_one_to_many(): void
    {
        $inverseSideFactory = persistent_factory(InversedOneToOneWithOneToMany\InverseSide::class);
        $owningSideFactory = persistent_factory(InversedOneToOneWithOneToMany\OwningSide::class);
        $itemFactory = persistent_factory(InversedOneToOneWithOneToMany\Item::class)
            // "with()" attribute emulates what would be found in the "defaults()" method in a real factory
            ->with(['owningSide' => $owningSideFactory]);

        $inverseSide = $inverseSideFactory->create([
            'owningSide' => $owningSideFactory->with([
                'items' => $itemFactory->many(2),
            ]),
        ]);

        $owningSideFactory::assert()->count(1);
        $inverseSideFactory::assert()->count(1);
        $itemFactory::assert()->count(2);

        self::assertSame($inverseSide, $inverseSide->getOwningSide()?->inverseSide);
        self::assertCount(2, $inverseSide->getOwningSide()->getItems());
    }

    /**
     * @test
     */
    public function many_to_many_to_self_referencing_inverse_side(): void
    {
        $owningSideFactory = persistent_factory(ManyToOneToSelfReferencing\OwningSide::class);
        $inverseSideFactory = persistent_factory(ManyToOneToSelfReferencing\SelfReferencingInverseSide::class);

        $owningSideFactory->create(['inverseSide' => $inverseSideFactory]);

        $owningSideFactory::assert()->count(1);
        $inverseSideFactory::assert()->count(1);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(IndexedOneToMany\ParentEntity::class, ['items'])]
    #[RequiresPhpunit('^11.4')]
    public function indexed_one_to_many(): void
    {
        $parentFactory = persistent_factory(IndexedOneToMany\ParentEntity::class);
        $childFactory = persistent_factory(IndexedOneToMany\Child::class);

        $parent = $parentFactory->create(
            [
                'items' => $childFactory->with(['language' => 'en', 'parent' => $parentFactory])->many(1),
            ]
        );

        $parentFactory::assert()->count(1);
        $childFactory::assert()->count(1);

        self::assertNotNull($parent->getItems()->get('en')); // @phpstan-ignore argument.type
    }

    /** @test */
    #[Test]
    public function object_with_union_type(): void
    {
        $owningSideFactory = persistent_factory(OneToManyWithUnionType\OwningSideEntity::class);
        $hasOneToManyWithUnionTypeFactory = persistent_factory(OneToManyWithUnionType\HasOneToManyWithUnionType::class);

        $object = $hasOneToManyWithUnionTypeFactory->create(
            [
                'collection' => $owningSideFactory->many(2),
            ]
        );

        $owningSideFactory::assert()->count(2);
        $hasOneToManyWithUnionTypeFactory::assert()->count(1);

        self::assertCount(2, $object->collection);
        self::assertInstanceOf(Collection::class, $object->collection);
    }

    /**
     * @test
     */
    public function inversed_multiple_mandatory_relationship_to_same_entity(): void
    {
        $this->markTestIncomplete('fixme! 🙏');

        // @phpstan-ignore-next-line
        $inversedSideEntity = MultipleMandatoryRelationshipToSameEntity\InversedSideEntityFactory::createOne([
            'mainRelations' => MultipleMandatoryRelationshipToSameEntity\OwningSideEntityFactory::new()->many(2),
            'secondaryRelations' => MultipleMandatoryRelationshipToSameEntity\OwningSideEntityFactory::new()->many(2),
        ]);

        $this->assertCount(2, $inversedSideEntity->getMainRelations());
        $this->assertCount(2, $inversedSideEntity->getSecondaryRelations());
        MultipleMandatoryRelationshipToSameEntity\OwningSideEntityFactory::assert()->count(4);
        MultipleMandatoryRelationshipToSameEntity\InversedSideEntityFactory::assert()->count(1);
    }
}
