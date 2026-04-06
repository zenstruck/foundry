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

    #[Test]
    public function testDoctrineEventsAreCalledByDefault(): void
    {
        $entity = EntityForDoctrineEventsFactory::createOne(['name' => 'test']);

        self::assertSame('test (from Doctrine event)', $entity->name);
    }

    #[Test]
    public function testItCanDisableAllDoctrineEvents(): void
    {
        $entity = EntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    #[Test]
    public function testItCanDisableSpecificDoctrineEventListener(): void
    {
        $entity = EntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents(DoctrineEventsSubscriber::class)
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    #[Test]
    public function testDoctrineEventsAreRestoredAfterCreation(): void
    {
        EntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'first']);

        // Events must be restored for subsequent factories
        $entity = EntityForDoctrineEventsFactory::createOne(['name' => 'second']);

        self::assertSame('second (from Doctrine event)', $entity->name);
    }

    // --- flush_after() ---

    #[Test]
    public function testItThrowsWhenUsedInsideFlushAfter(): void
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

    #[Test]
    public function testOrmEntityListenerIsCalledByDefault(): void
    {
        $entity = EntityWithOrmEntityListenerFactory::createOne(['name' => 'test']);

        self::assertSame('test (from ORM entity listener)', $entity->name);
    }

    #[Test]
    public function testItCanDisableAllOrmEntityListeners(): void
    {
        $entity = EntityWithOrmEntityListenerFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    #[Test]
    public function testItCanDisableSpecificOrmEntityListener(): void
    {
        $entity = EntityWithOrmEntityListenerFactory::new()
            ->withoutDoctrineEvents(OrmEntityListener::class)
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    #[Test]
    public function testOrmEntityListenerIsRestoredAfterCreation(): void
    {
        EntityWithOrmEntityListenerFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'first']);

        $entity = EntityWithOrmEntityListenerFactory::createOne(['name' => 'second']);

        self::assertSame('second (from ORM entity listener)', $entity->name);
    }

    // --- #[AsEntityListener] ---

    #[Test]
    public function testAsEntityListenerIsCalledByDefault(): void
    {
        $entity = EntityWithAsEntityListenerFactory::createOne(['name' => 'test']);

        self::assertSame('test (from AsEntityListener)', $entity->name);
    }

    #[Test]
    public function testItCanDisableAllAsEntityListeners(): void
    {
        $entity = EntityWithAsEntityListenerFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    #[Test]
    public function testItCanDisableSpecificAsEntityListener(): void
    {
        $entity = EntityWithAsEntityListenerFactory::new()
            ->withoutDoctrineEvents(AsEntityListenerListener::class)
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    #[Test]
    public function testAsEntityListenerIsRestoredAfterCreation(): void
    {
        EntityWithAsEntityListenerFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'first']);

        $entity = EntityWithAsEntityListenerFactory::createOne(['name' => 'second']);

        self::assertSame('second (from AsEntityListener)', $entity->name);
    }

    // --- Relations: ManyToOne (child → parent) ---

    #[Test]
    public function testEventsAreCalledByDefaultOnChildAndParent(): void
    {
        $child = ChildEntityForDoctrineEventsFactory::createOne(['name' => 'child']);

        self::assertSame('child (from Doctrine event)', $child->name);
        self::assertNotNull($child->parent);
        self::assertStringEndsWith('(from Doctrine event)', $child->parent->name);
    }

    #[Test]
    public function testWithoutDoctrineEventsPropagatesFromChildToParent(): void
    {
        $child = ChildEntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'child']);

        self::assertSame('child', $child->name);
        self::assertNotNull($child->parent);
        self::assertStringNotContainsString('(from Doctrine event)', $child->parent->name);
    }

    // --- Relations: OneToMany (parent → children) ---

    #[Test]
    public function testEventsAreCalledByDefaultOnParentAndChildren(): void
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

    #[Test]
    public function testWithoutDoctrineEventsPropagatesFromParentToChildren(): void
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
