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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\CascadeCtorRequiredParent;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\CascadePersistChain;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\OrphanRemoval;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * Lifecycle events must never observe a half-built object graph: em->persist() is
 * deferred until the whole graph is instantiated and wired.
 *
 * @see https://github.com/zenstruck/foundry/issues/1100
 * @see https://github.com/zenstruck/foundry/issues/1115
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DeferredPersistTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /** @test */
    #[Test]
    public function pre_persist_can_access_populated_one_to_many_collection(): void
    {
        $aFactory = persistent_factory(CascadePersistChain\ChainA::class);
        $bFactory = persistent_factory(CascadePersistChain\ChainB::class);
        $cFactory = persistent_factory(CascadePersistChain\ChainC::class);

        $a = $aFactory->create([
            'bs' => $bFactory->with(['cs' => $cFactory->many(1)])->many(1),
        ]);

        $aFactory::assert()->count(1);
        $bFactory::assert()->count(1);
        $cFactory::assert()->count(1);

        self::assertSame(1, $a->bsCountAtPrePersist);

        $b = $a->getBs()->first();
        self::assertNotFalse($b);
        self::assertTrue($b->hasAAtPrePersist);
        self::assertSame(1, $b->csCountAtPrePersist);

        $c = $b->getCs()->first();
        self::assertNotFalse($c);
        self::assertTrue($c->hasBAtPrePersist);
    }

    /** @test */
    #[Test]
    public function pre_persist_sees_populated_collections_when_created_from_child_side(): void
    {
        $aFactory = persistent_factory(CascadePersistChain\ChainA::class);
        $bFactory = persistent_factory(CascadePersistChain\ChainB::class);
        $cFactory = persistent_factory(CascadePersistChain\ChainC::class);

        $c = $cFactory->create(['b' => $bFactory->with(['a' => $aFactory])]);

        $b = $c->getB();
        self::assertNotNull($b);
        $a = $b->getA();
        self::assertNotNull($a);

        self::assertTrue($c->hasBAtPrePersist);
        self::assertTrue($b->hasAAtPrePersist);
        self::assertSame(1, $b->csCountAtPrePersist);
        self::assertSame(1, $a->bsCountAtPrePersist);
    }

    /** @test */
    #[Test]
    public function pre_persist_sees_populated_collection_with_unpersisted_parent_instance(): void
    {
        $bFactory = persistent_factory(CascadePersistChain\ChainB::class);

        $b = $bFactory->create(['a' => $a = new CascadePersistChain\ChainA()]);

        self::assertTrue($b->hasAAtPrePersist);
        self::assertSame(1, $a->bsCountAtPrePersist);
    }

    /** @test */
    #[Test]
    public function pre_persist_sees_children_when_child_constructor_requires_parent_with_cascade(): void
    {
        $parentFactory = persistent_factory(CascadeCtorRequiredParent\ParentEntity::class);
        $childFactory = persistent_factory(CascadeCtorRequiredParent\ChildEntity::class);

        $parent = $parentFactory->create(['children' => $childFactory->many(2)]);

        self::assertSame(2, $parent->childrenCountAtPrePersist);
        $parentFactory::assert()->count(1);
        $childFactory::assert()->count(2);
    }

    /** @test */
    #[Test]
    public function pre_persist_sees_child_when_created_from_child_side_with_cascade(): void
    {
        $parentFactory = persistent_factory(CascadeCtorRequiredParent\ParentEntity::class);
        $childFactory = persistent_factory(CascadeCtorRequiredParent\ChildEntity::class);

        $child = $childFactory->create(['parent' => $parentFactory]);

        self::assertSame(1, $child->parent->childrenCountAtPrePersist);
        $childFactory::assert()->count(1);
    }

    /**
     * @test
     *
     * @see https://github.com/zenstruck/foundry/pull/1134#issuecomment-5021148915
     */
    #[Test]
    public function lifecycle_state_of_nested_objects_is_visible_below_schedule_for_insert_priority(): void
    {
        $aFactory = persistent_factory(CascadePersistChain\ChainA::class);
        $bFactory = persistent_factory(CascadePersistChain\ChainB::class);
        $cFactory = persistent_factory(CascadePersistChain\ChainC::class);

        $hasAAtDefaultPriority = null;
        $stateAfterInsert = null;

        $cFactory
            ->afterInstantiate(static function(CascadePersistChain\ChainC $c) use (&$hasAAtDefaultPriority): void {
                $hasAAtDefaultPriority = $c->getB()?->hasAAtPrePersist;
            })
            ->afterInstantiate(static function(CascadePersistChain\ChainC $c) use (&$stateAfterInsert): void {
                $stateAfterInsert = [
                    'hasAAtPrePersist' => $c->getB()?->hasAAtPrePersist,
                    'bsCountAtPrePersist' => $c->getB()?->getA()?->bsCountAtPrePersist,
                ];
            }, PersistentObjectFactory::PRIORITY_SCHEDULE_FOR_INSERT - 1)
            ->create(['b' => $bFactory->with(['a' => $aFactory])]);

        self::assertFalse($hasAAtDefaultPriority);
        self::assertSame(['hasAAtPrePersist' => true, 'bsCountAtPrePersist' => 1], $stateAfterInsert);
    }

    /** @test */
    #[Test]
    public function lifecycle_state_of_collection_items_is_visible_below_schedule_for_insert_priority(): void
    {
        $aFactory = persistent_factory(CascadePersistChain\ChainA::class);
        $bFactory = persistent_factory(CascadePersistChain\ChainB::class);

        $hasAAtPrePersist = null;

        $aFactory
            ->afterInstantiate(static function(CascadePersistChain\ChainA $a) use (&$hasAAtPrePersist): void {
                $first = $a->getBs()->first();
                $hasAAtPrePersist = $first instanceof CascadePersistChain\ChainB ? $first->hasAAtPrePersist : null;
            }, PersistentObjectFactory::PRIORITY_SCHEDULE_FOR_INSERT - 1)
            ->create(['bs' => $bFactory->many(1)]);

        self::assertTrue($hasAAtPrePersist);
    }

    /** @test */
    #[Test]
    public function orphan_removal_does_not_delete_children_on_subsequent_flushes(): void
    {
        $parentFactory = persistent_factory(OrphanRemoval\ParentEntity::class);
        $childFactory = persistent_factory(OrphanRemoval\ChildEntity::class);

        $parent = $parentFactory->create(['children' => $childFactory->many(2)]);

        self::assertSame(2, $parent->childrenCount);
        $childFactory::assert()->count(2);

        $parent->name = 'updated';
        self::entityManager()->flush();

        $childFactory::assert()->count(2);
        self::assertSame(2, $parent->childrenCount);
    }

    /** @test */
    #[Test]
    public function only_one_flush_per_root_create(): void
    {
        $aFactory = persistent_factory(CascadePersistChain\ChainA::class);
        $bFactory = persistent_factory(CascadePersistChain\ChainB::class);
        $cFactory = persistent_factory(CascadePersistChain\ChainC::class);

        $eventManager = self::entityManager()->getEventManager();
        $listener = new class {
            public int $flushes = 0;

            public function postFlush(): void
            {
                ++$this->flushes;
            }
        };
        $eventManager->addEventListener([Events::postFlush], $listener);

        try {
            $cFactory->create(['b' => $bFactory->with(['a' => $aFactory])]);
        } finally {
            $eventManager->removeEventListener([Events::postFlush], $listener);
        }

        self::assertSame(1, $listener->flushes);
    }

    /** @test */
    #[Test]
    public function a_failing_create_does_not_leak_scheduled_objects_into_the_next_one(): void
    {
        $aFactory = persistent_factory(CascadePersistChain\ChainA::class);
        $bFactory = persistent_factory(CascadePersistChain\ChainB::class);

        try {
            $aFactory
                ->afterInstantiate(static function(): void { throw new \RuntimeException('boom'); })
                ->create(['bs' => $bFactory->many(2)]);

            self::fail('create() should have thrown');
        } catch (\RuntimeException) {
        }

        persistent_factory(OrphanRemoval\ParentEntity::class)->create();

        $aFactory::assert()->count(0);
        $bFactory::assert()->count(0);
    }

    /** @test */
    #[Test]
    public function a_failing_item_in_a_flush_once_batch_does_not_leak_scheduled_objects(): void
    {
        $parentFactory = persistent_factory(OrphanRemoval\ParentEntity::class);
        $count = 0;

        try {
            $parentFactory
                ->afterInstantiate(static function() use (&$count): void {
                    if (2 === ++$count) {
                        throw new \RuntimeException('boom');
                    }
                })
                ->many(3)
                ->create();

            self::fail('create() should have thrown');
        } catch (\RuntimeException) {
        }

        persistent_factory(OrphanRemoval\ChildEntity::class)->create();

        $parentFactory::assert()->count(0);
    }

    private static function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class); // @phpstan-ignore return.type
    }
}
