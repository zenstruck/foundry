<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\ChangesEntityRelationshipCascadePersist;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationships;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipWithGlobalEntity;

use function Zenstruck\Foundry\Persistence\flush_after;
use function Zenstruck\Foundry\Persistence\persistent_factory;

final class OrmEdgeCaseTest extends ResetDatabaseTestCase
{
    use ChangesEntityRelationshipCascadePersist;

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(RelationshipWithGlobalEntity\RelationshipWithGlobalEntity::class, ['globalEntity'])]
    #[RequiresPhpunit('>=11.4')]
    public function it_can_use_flush_after_and_entity_from_global_state(): void
    {
        $relationshipWithGlobalEntityFactory = persistent_factory(RelationshipWithGlobalEntity\RelationshipWithGlobalEntity::class);
        $globalEntitiesCount = persistent_factory(GlobalEntity::class)::repository()->count();

        flush_after(function() use ($relationshipWithGlobalEntityFactory) {
            $relationshipWithGlobalEntityFactory->create(['globalEntity' => GlobalStory::globalEntityProxy()]);
            $relationshipWithGlobalEntityFactory->create(['globalEntity' => GlobalStory::globalEntity()]);
        });

        // assert no extra GlobalEntity have been created
        persistent_factory(GlobalEntity::class)::assert()->count($globalEntitiesCount);

        $relationshipWithGlobalEntityFactory::assert()->count(2);

        $entity = $relationshipWithGlobalEntityFactory::repository()->first();
        self::assertSame(GlobalStory::globalEntity(), $entity?->getGlobalEntity());

        $entity = $relationshipWithGlobalEntityFactory::repository()->last();
        self::assertSame(GlobalStory::globalEntity(), $entity?->getGlobalEntity());
    }
}
