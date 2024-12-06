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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\InversedOneToOneWithNonNullableOwning;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\ManyToOneToSelfReferencing;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipWithGlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RichDomainMandatoryRelationship;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\EdgeCases\MultipleMandatoryRelationshipToSameEntity;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\flush_after;
use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\proxy_factory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class EdgeCasesRelationshipTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /**
     * @test
     * @param PersistentObjectFactory<RelationshipWithGlobalEntity\RelationshipWithGlobalEntity> $relationshipWithGlobalEntityFactory
     * @dataProvider relationshipWithGlobalEntityFactoryProvider
     */
    public function it_can_use_flush_after_and_entity_from_global_state(PersistentObjectFactory $relationshipWithGlobalEntityFactory, bool $asProxy): void
    {
        $globalEntitiesCount = persistent_factory(GlobalEntity::class)::repository()->count();

        flush_after(function() use ($relationshipWithGlobalEntityFactory, $asProxy) {
            $globalEntity = $asProxy ? GlobalStory::globalEntityProxy() : GlobalStory::globalEntity();
            self::assertSame($asProxy, $globalEntity instanceof Proxy);

            $relationshipWithGlobalEntityFactory->create(['globalEntity' => $globalEntity]);
        });

        // assert no extra GlobalEntity have been created
        persistent_factory(GlobalEntity::class)::assert()->count($globalEntitiesCount);

        $relationshipWithGlobalEntityFactory::assert()->count(1);

        $entity = $relationshipWithGlobalEntityFactory::repository()->first();
        self::assertSame(GlobalStory::globalEntity(), $entity?->getGlobalEntity());
    }

    public static function relationshipWithGlobalEntityFactoryProvider(): iterable
    {
        yield [persistent_factory(RelationshipWithGlobalEntity\StandardRelationshipWithGlobalEntity::class), false];
        yield [persistent_factory(RelationshipWithGlobalEntity\CascadeRelationshipWithGlobalEntity::class), false];
        yield [persistent_factory(RelationshipWithGlobalEntity\StandardRelationshipWithGlobalEntity::class), true];
        yield [persistent_factory(RelationshipWithGlobalEntity\CascadeRelationshipWithGlobalEntity::class), true];
    }

    /**
     * @test
     * @param PersistentObjectFactory<RichDomainMandatoryRelationship\InversedSideEntity> $inversedSideEntityFactory
     * @param PersistentObjectFactory<RichDomainMandatoryRelationship\OwningSideEntity>   $owningSideEntityFactory
     * @dataProvider richDomainMandatoryRelationshipFactoryProvider
     */
    public function inversed_relationship_mandatory(PersistentObjectFactory $inversedSideEntityFactory, PersistentObjectFactory $owningSideEntityFactory): void
    {
        $inversedSideEntity = $inversedSideEntityFactory->create([
            'relations' => $owningSideEntityFactory->many(2),
        ]);

        $this->assertCount(2, $inversedSideEntity->getRelations());
        $owningSideEntityFactory::assert()->count(2);
        $inversedSideEntityFactory::assert()->count(1);
    }

    public static function richDomainMandatoryRelationshipFactoryProvider(): iterable
    {
        yield [
            persistent_factory(RichDomainMandatoryRelationship\StandardInversedSideEntity::class),
            persistent_factory(RichDomainMandatoryRelationship\StandardOwningSideEntity::class),
        ];
        yield [
            proxy_factory(RichDomainMandatoryRelationship\CascadeInversedSideEntity::class),
            proxy_factory(RichDomainMandatoryRelationship\CascadeOwningSideEntity::class),
        ];
        yield [
            persistent_factory(RichDomainMandatoryRelationship\StandardInversedSideEntity::class),
            persistent_factory(RichDomainMandatoryRelationship\StandardOwningSideEntity::class),
        ];
        yield [
            proxy_factory(RichDomainMandatoryRelationship\CascadeInversedSideEntity::class),
            proxy_factory(RichDomainMandatoryRelationship\CascadeOwningSideEntity::class),
        ];
    }

    /**
     * @test
     */
    public function inverse_one_to_one_with_non_nullable_inverse_side(): void
    {
        $owningSideFactory = persistent_factory(InversedOneToOneWithNonNullableOwning\OwningSide::class);
        $inverseSideFactory = persistent_factory(InversedOneToOneWithNonNullableOwning\InverseSide::class);

        $inverseSide = $inverseSideFactory->create(['owningSide' => $owningSideFactory]);

        $owningSideFactory::assert()->count(1);
        $inverseSideFactory::assert()->count(1);

        self::assertSame($inverseSide, $inverseSide->owningSide->inverseSide);
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
