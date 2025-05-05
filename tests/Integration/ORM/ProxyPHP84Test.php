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
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy\PersistedObjectsTracker;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;
use Zenstruck\Foundry\Tests\Integration\Persistence\ProxyPHP84TestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

final class ProxyPHP84Test extends ProxyPHP84TestCase
{
    use RequiresORM;

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
    public function tracker_keeps_reference_only_for_objects_in_current_scope(): void
    {
        [$genericEntity] = GenericEntityFactory::new()->many(2)->create();
        ContactFactory::new()->many(2)->create();

        // 8 = 2 GenericEntity + 2 Contact + 2 Address + 2 Category
        self::assertSame(8, PersistedObjectsTracker::countObjects());

        self::ensureKernelShutdown();

        // kernel shutdown cleared the EM, then one of the generic entities was removed from tracker
        // all other entities are kept, because they have circular references
        self::assertSame(7, PersistedObjectsTracker::countObjects());

        \gc_collect_cycles();

        // after gc collect, all entities created by ContactFactory are removed from tracker
        self::assertSame(1, PersistedObjectsTracker::countObjects());

        // refreshing again won't clear the tracked object because a reference still exists incurrent scope
        Configuration::instance()->persistedObjectsTracker?->refresh();
        self::assertSame(1, PersistedObjectsTracker::countObjects());

        unset($genericEntity);
        Configuration::instance()->persistedObjectsTracker?->refresh();

        // unsetting the generic entity will remove it from the tracker as well
        self::assertSame(0, PersistedObjectsTracker::countObjects());
    }

    protected static function factory(): PersistentObjectFactory
    {
        return GenericEntityFactory::new();
    }

    protected function dbms(): string
    {
        return 'orm';
    }

    protected function updateObject(GenericModel $object): void
    {
        $this->em()->getConnection()->executeQuery('UPDATE generic_entity SET prop1 = \'foo\' WHERE id = ?', [$object->id]);
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class); // @phpstan-ignore return.type
    }
}
