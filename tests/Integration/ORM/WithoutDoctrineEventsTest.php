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
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\AsEntityListenerListener;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\ChildEntityForDoctrineEventsFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\ChildEntityWithoutAsEntityListenerFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\DoctrineEventsSubscriber;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityForDoctrineEventsFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityWithAsEntityListenerFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityWithDeepListenedRelationFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityWithListenedRelationFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityWithListenedRelationListener;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityWithOrmEntityListenerFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityWithoutAsEntityListenerFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\ListenedEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\ListenedEntityListener;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\OrmEntityListener;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\ParentEntityForDoctrineEventsFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\ParentOfListenedEntitiesFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityWithAsEntityListener;
use Zenstruck\Foundry\Tests\Fixture\Entity\ListenedEntity;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\flush_after;

final class WithoutDoctrineEventsTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /**
     * @test
     */
    #[Test]
    public function doctrine_events_are_called_by_default(): void
    {
        $entity = EntityForDoctrineEventsFactory::createOne(['name' => 'test']);

        self::assertSame('test (from Doctrine event)', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_disable_all_doctrine_events(): void
    {
        $entity = EntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_disable_specific_doctrine_event_listener(): void
    {
        $entity = EntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents(DoctrineEventsSubscriber::class)
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function doctrine_events_are_restored_after_creation(): void
    {
        EntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'first']);

        // Events must be restored for subsequent factories
        $entity = EntityForDoctrineEventsFactory::createOne(['name' => 'second']);

        self::assertSame('second (from Doctrine event)', $entity->name);
    }

    // --- flush_after() ---

    /**
     * @test
     */
    #[Test]
    public function it_throws_when_used_inside_flush_after(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('withoutDoctrineEvents() cannot be used inside flush_after().');

        flush_after(static function(): void {
            EntityForDoctrineEventsFactory::new()
                ->withoutDoctrineEvents()
                ->create(['name' => 'test']);
        });
    }

    // --- #[ORM\EntityListeners] ---

    /**
     * @test
     */
    #[Test]
    public function orm_entity_listener_is_called_by_default(): void
    {
        $entity = EntityWithOrmEntityListenerFactory::createOne(['name' => 'test']);

        self::assertSame('test (from ORM entity listener)', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_disable_all_orm_entity_listeners(): void
    {
        $entity = EntityWithOrmEntityListenerFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_disable_specific_orm_entity_listener(): void
    {
        $entity = EntityWithOrmEntityListenerFactory::new()
            ->withoutDoctrineEvents(OrmEntityListener::class)
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function orm_entity_listener_is_restored_after_creation(): void
    {
        EntityWithOrmEntityListenerFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'first']);

        $entity = EntityWithOrmEntityListenerFactory::createOne(['name' => 'second']);

        self::assertSame('second (from ORM entity listener)', $entity->name);
    }

    // --- #[AsEntityListener] ---

    /**
     * @test
     */
    #[Test]
    public function as_entity_listener_is_called_by_default(): void
    {
        $entity = EntityWithAsEntityListenerFactory::createOne(['name' => 'test']);

        self::assertSame('test (from AsEntityListener)', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_disable_all_as_entity_listeners(): void
    {
        $entity = EntityWithAsEntityListenerFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_disable_specific_as_entity_listener(): void
    {
        $entity = EntityWithAsEntityListenerFactory::new()
            ->withoutDoctrineEvents(AsEntityListenerListener::class)
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function as_entity_listener_is_restored_after_creation(): void
    {
        EntityWithAsEntityListenerFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'first']);

        $entity = EntityWithAsEntityListenerFactory::createOne(['name' => 'second']);

        self::assertSame('second (from AsEntityListener)', $entity->name);
    }

    // --- Relations: ORM entity listeners on related entity (reproducer from PR #1131) ---

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_disables_orm_entity_listener_on_cascade_persisted_related_entity(): void
    {
        AsEntityListenerListener::$postPersistExecuted = false;
        $child = ChildEntityWithoutAsEntityListenerFactory::createOne(['name' => 'child']);

        self::assertNotNull($child->parent);
        self::assertStringNotContainsString('(from AsEntityListener)', $child->parent->name);
        self::assertFalse(AsEntityListenerListener::$postPersistExecuted);
    }

    /**
     * @test
     */
    #[Test]
    public function same_factory_instance_can_create_twice_with_events_disabled(): void
    {
        AsEntityListenerListener::$postPersistExecuted = false;
        $factory = EntityWithoutAsEntityListenerFactory::new();

        $first = $factory->create(['name' => 'first']);
        $second = $factory->create(['name' => 'second']);

        self::assertSame('first', $first->name);
        self::assertSame('second', $second->name);
        self::assertFalse(AsEntityListenerListener::$postPersistExecuted);

        $third = EntityWithAsEntityListenerFactory::createOne(['name' => 'third']);

        self::assertSame('third (from AsEntityListener)', $third->name);
        self::assertTrue(AsEntityListenerListener::$postPersistExecuted);
    }

    // --- Metadata loaded lazily during the disabling window ---

    /**
     * @test
     */
    #[Test]
    public function it_keeps_as_entity_listeners_of_classes_whose_metadata_loads_during_the_window(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        // ensure the metadata of EntityWithAsEntityListener is built for the very first time inside the window
        $em->getConfiguration()->getMetadataCache()?->clear();

        EntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            // triggers the loadClassMetadata event for this class inside the window
            ->afterInstantiate(static function() use ($em): void {
                $em->getClassMetadata(EntityWithAsEntityListener::class);
            })
            ->create(['name' => 'test']);

        // outside the window, the #[AsEntityListener] must still be registered
        $entity = EntityWithAsEntityListenerFactory::createOne(['name' => 'second']);

        self::assertSame('second (from AsEntityListener)', $entity->name);
    }

    // --- Relations: ManyToOne (child → parent) ---

    /**
     * @test
     */
    #[Test]
    public function events_are_called_by_default_on_child_and_parent(): void
    {
        $child = ChildEntityForDoctrineEventsFactory::createOne(['name' => 'child']);

        self::assertSame('child (from Doctrine event)', $child->name);
        self::assertNotNull($child->parent);
        self::assertStringEndsWith('(from Doctrine event)', $child->parent->name);
    }

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_propagates_from_child_to_parent(): void
    {
        $child = ChildEntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'child']);

        self::assertSame('child', $child->name);
        self::assertNotNull($child->parent);
        self::assertStringNotContainsString('(from Doctrine event)', $child->parent->name);
    }

    // --- Relations: OneToMany (parent → children) ---

    /**
     * @test
     */
    #[Test]
    public function events_are_called_by_default_on_parent_and_children(): void
    {
        $parent = ParentEntityForDoctrineEventsFactory::createOne([
            'name' => 'parent',
            'children' => ChildEntityForDoctrineEventsFactory::new()->many(2),
        ]);

        self::assertSame('parent (from Doctrine event)', $parent->name);
        self::assertCount(2, $parent->getChildren());

        foreach ($parent->getChildren() as $child) {
            self::assertStringEndsWith('(from Doctrine event)', $child->name);
        }
    }

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_propagates_from_parent_to_children(): void
    {
        $parent = ParentEntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create([
                'name' => 'parent',
                'children' => ChildEntityForDoctrineEventsFactory::new()->many(2),
            ]);

        self::assertSame('parent', $parent->name);

        foreach ($parent->getChildren() as $child) {
            self::assertStringNotContainsString('(from Doctrine event)', $child->name);
        }
    }

    // --- Relations: flush-time events (postPersist) on nested entities (#1129) ---

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_disables_post_persist_listener_on_nested_entity(): void
    {
        ListenedEntityListener::$postPersistCount = 0;

        $entity = EntityWithListenedRelationFactory::new()
            ->withoutDoctrineEvents(ListenedEntityListener::class)
            ->create([
                'name' => 'root',
                'listened' => ListenedEntityFactory::new()->withoutDoctrineEvents(ListenedEntityListener::class),
            ]);

        self::assertNotNull($entity->listened);
        self::assertStringNotContainsString('(from listener)', $entity->listened->name);
        self::assertSame(0, ListenedEntityListener::$postPersistCount);

        ListenedEntityFactory::createOne();

        self::assertSame(1, ListenedEntityListener::$postPersistCount);
    }

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_on_nested_factory_only_covers_the_root_flush(): void
    {
        ListenedEntityListener::$postPersistCount = 0;
        EntityWithListenedRelationListener::$postPersistCount = 0;

        $entity = EntityWithListenedRelationFactory::createOne([
            'name' => 'root',
            'listened' => ListenedEntityFactory::new()->withoutDoctrineEvents(ListenedEntityListener::class),
        ]);

        self::assertNotNull($entity->listened);
        self::assertStringNotContainsString('(from listener)', $entity->listened->name);
        self::assertSame(0, ListenedEntityListener::$postPersistCount);
        // listeners not targeted by the nested factory must still fire
        self::assertSame(1, EntityWithListenedRelationListener::$postPersistCount);

        ListenedEntityFactory::createOne();

        self::assertSame(1, ListenedEntityListener::$postPersistCount);
    }

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_merges_parent_and_nested_disabled_listeners(): void
    {
        ListenedEntityListener::$postPersistCount = 0;
        EntityWithListenedRelationListener::$postPersistCount = 0;

        $entity = EntityWithListenedRelationFactory::new()
            ->withoutDoctrineEvents(EntityWithListenedRelationListener::class)
            ->create([
                'name' => 'root',
                'listened' => ListenedEntityFactory::new()->withoutDoctrineEvents(ListenedEntityListener::class),
            ]);

        self::assertNotNull($entity->listened);
        self::assertStringNotContainsString('(from listener)', $entity->listened->name);
        self::assertSame(0, ListenedEntityListener::$postPersistCount);
        self::assertSame(0, EntityWithListenedRelationListener::$postPersistCount);

        EntityWithListenedRelationFactory::createOne();

        self::assertSame(1, ListenedEntityListener::$postPersistCount);
        self::assertSame(1, EntityWithListenedRelationListener::$postPersistCount);
    }

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_propagates_through_multiple_nesting_levels(): void
    {
        ListenedEntityListener::$postPersistCount = 0;

        $root = EntityWithDeepListenedRelationFactory::new()
            ->withoutDoctrineEvents(ListenedEntityListener::class)
            ->create(['name' => 'root']);

        self::assertNotNull($root->middle);
        self::assertNotNull($root->middle->listened);
        self::assertStringNotContainsString('(from listener)', $root->middle->listened->name);
        self::assertSame(0, ListenedEntityListener::$postPersistCount);

        ListenedEntityFactory::createOne();

        self::assertSame(1, ListenedEntityListener::$postPersistCount);
    }

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_covers_the_collective_flush_of_many(): void
    {
        ListenedEntityListener::$postPersistCount = 0;

        ListenedEntityFactory::new()
            ->withoutDoctrineEvents(ListenedEntityListener::class)
            ->many(2)
            ->create();

        self::assertSame(0, ListenedEntityListener::$postPersistCount);

        ListenedEntityFactory::createOne();

        self::assertSame(1, ListenedEntityListener::$postPersistCount);
    }

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_on_nested_collection_factory_covers_the_root_flush(): void
    {
        ListenedEntityListener::$postPersistCount = 0;

        $parent = ParentOfListenedEntitiesFactory::createOne([
            'name' => 'parent',
            'children' => ListenedEntityFactory::new()->withoutDoctrineEvents(ListenedEntityListener::class)->many(2),
        ]);

        self::assertCount(2, $parent->getChildren());

        foreach ($parent->getChildren() as $child) {
            self::assertStringNotContainsString('(from listener)', $child->name);
        }

        self::assertSame(0, ListenedEntityListener::$postPersistCount);

        ListenedEntityFactory::createOne();

        self::assertSame(1, ListenedEntityListener::$postPersistCount);
    }

    #[Test]
    #[RequiresPhpunit('>=11.4.0')]
    #[RequiresPhpunitExtension(FoundryExtension::class)]
    #[DataProvider('provideEntityWithDisabledListener')]
    public function deferred_factory_from_data_provider_does_not_leak_disabled_listeners(?ListenedEntity $entity): void
    {
        self::assertNotNull($entity);
        self::assertStringNotContainsString('(from listener)', $entity->name);

        ListenedEntityListener::$postPersistCount = 0;

        ListenedEntityFactory::createOne();

        self::assertSame(1, ListenedEntityListener::$postPersistCount);
    }

    public static function provideEntityWithDisabledListener(): iterable
    {
        yield [ListenedEntityFactory::new()->withoutDoctrineEvents(ListenedEntityListener::class)->create()];
    }
}
