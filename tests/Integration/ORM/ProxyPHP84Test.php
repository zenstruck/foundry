<?php

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Persistence\Proxy\CreatedObjectsTracker;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

final class ProxyPHP84Test extends WebTestCase
{
    use Factories;
    
    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
    public function it_can_refresh_objects_with_php84_proxies(): void
    {
        $object = GenericEntityFactory::createOne();
        self::ensureKernelShutdown();

        self::assertSame('default1', $object->getProp1());
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $client = self::createClient();
        $client->request('GET', "/update/{$object->id}");

        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));
        self::assertSame('foo', $object->getProp1());
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));
    }

    /**
     * @test
     * @requires PHP >= 8.4
     * @depends it_can_refresh_objects_with_php84_proxies
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
    #[Depends('it_can_refresh_objects_with_php84_proxies')]
    public function it_can_refresh_objects_with_php84_tracker_is_empty_after_test(): void
    {
        self::assertSame(0, CreatedObjectsTracker::countObjects());
    }

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
    public function tracker_only_keep_reference_for_objects_in_current_scope(): void
    {
        [$genericEntity] = GenericEntityFactory::new()->many(2)->create();
        ContactFactory::new()->many(2)->create();

        // 8 = 2 GenericEntity + 2 Contact + 2 Address + 2 Category
        self::assertSame(8, CreatedObjectsTracker::countObjects());
        self::assertSame(8, CreatedObjectsTracker::countObjectsWithValidRef());

        self::ensureKernelShutdown();

        self::assertSame(8, CreatedObjectsTracker::countObjects());
        // kernel shutdown cleared the EM, then one of the generic entities was removed from tracker
        // all other entities are kept, because they have circular references
        self::assertSame(7, CreatedObjectsTracker::countObjectsWithValidRef());

        gc_collect_cycles();

        self::assertSame(8, CreatedObjectsTracker::countObjects());

        // after gc collect, all entities created by ContactFactory are removed from tracker
        self::assertSame(1, CreatedObjectsTracker::countObjectsWithValidRef());

        CreatedObjectsTracker::proxifyObjects();

        // a call to proxifyObjects() will update the references in the tracker, to only keep the valid ones
        self::assertSame(1, CreatedObjectsTracker::countObjects());
        self::assertSame(1, CreatedObjectsTracker::countObjectsWithValidRef());

        unset($genericEntity);
        CreatedObjectsTracker::proxifyObjects();

        // unsetting the generic entity will remove it from the tracker as well
        self::assertSame(0, CreatedObjectsTracker::countObjects());
    }
}
