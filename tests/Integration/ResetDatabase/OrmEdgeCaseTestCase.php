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

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnorePhpunitWarnings;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\ChangesEntityRelationshipCascadePersist;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationships;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipWithGlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\ResetDatabaseTestKernel;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;
use Zenstruck\Foundry\Tests\Integration\ORM\EdgeCasesRelationshipTest;

use function Zenstruck\Foundry\Persistence\flush_after;
use function Zenstruck\Foundry\Persistence\persistent_factory;

abstract class OrmEdgeCaseTestCase extends KernelTestCase
{
    use ChangesEntityRelationshipCascadePersist;

    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(RelationshipWithGlobalEntity\RelationshipWithGlobalEntity::class, ['globalEntity'])]
    #[RequiresPhpunit('>=11.4')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    public function it_can_use_flush_after_and_entity_from_global_state(): void
    {
        $relationshipWithGlobalEntityFactory = persistent_factory(RelationshipWithGlobalEntity\RelationshipWithGlobalEntity::class);
        $globalEntitiesCount = persistent_factory(GlobalEntity::class)::repository()->count();

        flush_after(static function() use ($relationshipWithGlobalEntityFactory) {
            $relationshipWithGlobalEntityFactory->create(['globalEntity' => GlobalStory::globalEntity()]);
        });

        // assert no extra GlobalEntity have been created
        persistent_factory(GlobalEntity::class)::assert()->count($globalEntitiesCount);

        $relationshipWithGlobalEntityFactory::assert()->count(1);

        $entity = $relationshipWithGlobalEntityFactory::repository()->first();
        self::assertSame(GlobalStory::globalEntity(), $entity?->getGlobalEntity());

        $entity = $relationshipWithGlobalEntityFactory::repository()->last();
        self::assertSame(GlobalStory::globalEntity(), $entity?->getGlobalEntity());
    }

    protected static function getKernelClass(): string
    {
        return ResetDatabaseTestKernel::class;
    }
}
