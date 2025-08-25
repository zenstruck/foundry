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
use Doctrine\Persistence\Proxy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnorePhpunitWarnings;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy\PersistedObjectsTracker;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\ChangesEntityRelationshipCascadePersist;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationships;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\AutoRefreshTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\refresh_all;

/**
 * @requires PHPUnit >=12
 */
#[RequiresPhpunit('>=12')]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '1')]
#[RequiresPhp('>= 8.4')]
final class AutoRefreshTest extends AutoRefreshTestCase
{
    use ChangesEntityRelationshipCascadePersist, RequiresORM;

    #[Test]
    public function it_can_refresh_after_services_reset(): void
    {
        $object = $this->factory()->create();
        $objectId = $object->id;

        self::getContainer()->get('services_resetter')->reset(); // @phpstan-ignore method.notFound
        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $this->updateObject($objectId);

        self::assertSame('foo', $object->getProp1());

        // service reset did clear the EM, thus the object is not managed anymore
        self::assertFalse($this->objectManager()->contains($object));
    }

    #[Test]
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

        // refreshing again won't clear the tracked object because a reference still exists in current scope
        Configuration::instance()->persistedObjectsTracker?->refresh();
        self::assertSame(1, PersistedObjectsTracker::countObjects());

        unset($genericEntity);

        // unsetting the generic entity will remove it from the tracker as well
        self::assertSame(0, PersistedObjectsTracker::countObjects());
    }

    #[Test]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category', 'address'])]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    public function it_can_refresh_objects_with_relationships(): void
    {
        $contact = ContactFactory::createOne([
            'address' => AddressFactory::new(['city' => 'city']),
            'category' => CategoryFactory::new(['name' => 'name']),
            'name' => 'name',
        ]);

        $address = $contact->getAddress();
        $category = $contact->getCategory();

        refresh_all();

        self::assertTrue((new \ReflectionClass($contact))->isUninitializedLazyObject($contact));
        self::assertTrue((new \ReflectionClass($address))->isUninitializedLazyObject($address));

        self::assertNotNull($category);
        self::assertTrue((new \ReflectionClass($category))->isUninitializedLazyObject($category));

        self::assertSame($address, $contact->getAddress());
        self::assertSame($category, $contact->getCategory());

        self::assertSame('name', $contact->getCategory()->getName());
        self::assertSame('city', $contact->getAddress()->getCity());
    }

    #[Test]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    public function it_can_refresh_with_doctrine_proxies(): void
    {
        $contact = ContactFactory::createOne();

        $address = $contact->getAddress();
        $category = $contact->getCategory();
        self::assertNotNull($category);

        $this->objectManager()->getConnection()->executeQuery('UPDATE address SET city = \'foo\' WHERE id = ?', [$address->id]);
        $this->objectManager()->getConnection()->executeQuery('UPDATE category SET name = \'foo\' WHERE id = ?', [$category->id]);

        self::ensureKernelShutdown();

        self::assertTrue((new \ReflectionClass($contact))->isUninitializedLazyObject($contact));
        self::assertTrue((new \ReflectionClass($address))->isUninitializedLazyObject($address));
        self::assertTrue((new \ReflectionClass($category))->isUninitializedLazyObject($category));

        self::assertInstanceOf(Proxy::class, $contact->getAddress());
        self::assertInstanceOf(Proxy::class, $contact->getCategory());

        self::assertNotSame($address, $contact->getAddress());
        self::assertNotSame($category, $contact->getCategory());

        self::assertSame($address->getCity(), $contact->getAddress()->getCity());
        self::assertSame($category->getName(), $contact->getCategory()->getName());

        self::assertSame('foo', $address->getCity());
        self::assertSame('foo', $category->getName());
    }

    protected static function factory(): PersistentObjectFactory
    {
        return GenericEntityFactory::new();
    }

    protected function dbms(): string
    {
        return 'orm';
    }

    protected function updateObject(mixed $objectId): void
    {
        $this->objectManager()->getConnection()->executeQuery(
            'UPDATE generic_entity SET prop1 = \'foo\' WHERE id = ?',
            [$objectId]
        );
    }

    protected function objectManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class); // @phpstan-ignore return.type
    }
}
