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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\AsEntityListenerListener;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\ChildEntityForDoctrineEventsFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\ChildEntityWithoutAsEntityListenerFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\DoctrineEventsSubscriber;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityForDoctrineEventsFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityWithAsEntityListenerFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityWithOrmEntityListenerFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\OrmEntityListener;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\ParentEntityForDoctrineEventsFactory;
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

    // --- Relations: ORM entity listeners on cascade-persisted related entity ---

    /**
     * @test
     */
    #[Test]
    public function without_doctrine_events_disables_orm_entity_listener_on_cascade_persisted_related_entity(): void
    {
        AsEntityListenerListener::$postPersistExecuted = false;
        $child = ChildEntityWithoutAsEntityListenerFactory::createOne(['name' => 'child']);

        self::assertNotNull($child->parent);
        self::assertStringNotContainsString('(from Doctrine event)', $child->parent->name);
        self::assertFalse(AsEntityListenerListener::$postPersistExecuted);
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
}
