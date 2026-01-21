<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use Composer\InstalledVersions;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\PersistedObjectsTracker;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\EntityWithReadonly\EntityWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\Persistence\assert_not_persisted;
use function Zenstruck\Foundry\Persistence\assert_persisted;
use function Zenstruck\Foundry\Persistence\flush_after;
use function Zenstruck\Foundry\Persistence\refresh;
use function Zenstruck\Foundry\Persistence\refresh_all;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12
 */
#[RequiresPhpunit('>=12')]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '1')]
#[RequiresPhp('>= 8.4')]
abstract class AutoRefreshTestCase extends WebTestCase
{
    use Factories, ResetDatabase;

    #[Test]
    public function it_can_refresh_after_services_reset(): void
    {
        $object = $this->factory()->create();
        $objectId = $object->id;

        self::getContainer()->get('services_resetter')->reset(); // @phpstan-ignore method.notFound
        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $this->updateObject($objectId);

        self::assertSame('foo', $object->getProp1());

        if ($this->objectManager() instanceof DocumentManager && \version_compare(InstalledVersions::getVersion('doctrine/mongodb-odm-bundle') ?? '', '5.4.3', '<')) {
            return;
        }

        // service reset did clear the EM, thus the object is not managed anymore
        self::assertFalse($this->objectManager()->contains($object));
    }

    #[Test]
    public function it_can_refresh_after_kernel_shutdown(): void
    {
        $object = $this->factory()->create();
        $objectId = $object->id;

        self::ensureKernelShutdown();
        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $this->updateObject($objectId);

        self::assertSame('foo', $object->getProp1());

        // service reset did clear the EM, thus the object is not managed anymore
        self::assertFalse($this->objectManager()->contains($object));
        self::assertSame($objectId, $object->id);
    }

    #[Test]
    public function it_can_refresh_many_objects(): void
    {
        [$object1, $object2] = $this->factory()->many(2)->create();
        $objectId1 = $object1->id;
        $objectId2 = $object2->id;

        self::getContainer()->get('services_resetter')->reset(); // @phpstan-ignore method.notFound
        self::assertTrue((new \ReflectionClass($object1))->isUninitializedLazyObject($object1));
        self::assertTrue((new \ReflectionClass($object2))->isUninitializedLazyObject($object2));

        $this->updateObject($objectId1);
        $this->updateObject($objectId2);

        self::assertSame('foo', $object1->getProp1());
        self::assertSame('foo', $object2->getProp1());

        self::assertSame($objectId1, $object1->id);
        self::assertSame($objectId2, $object2->id);
    }

    #[Test]
    public function it_can_refresh_after_update_with_browser(): void
    {
        $client = self::createClient();

        $object = $this->factory()->create();
        $objectId = $object->id;

        self::assertSame('default1', $object->getProp1());
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $client->request('GET', "/{$this->dbms()}/update/{$object->id}");
        self::assertResponseIsSuccessful();

        self::assertTrue($this->objectManager()->contains($object));

        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));
        assert_persisted($object);
        self::assertSame('foo', $object->getProp1());
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        self::assertSame($objectId, $object->id);
    }

    #[Test]
    public function it_can_refresh_twice_using_http_client(): void
    {
        $client = self::createClient();

        $object = $this->factory()->create();
        self::assertSame('default1', $object->getProp1());

        $client->request('GET', "/{$this->dbms()}/update/{$object->id}/foo");
        self::assertResponseIsSuccessful();
        self::assertSame('foo', $object->getProp1());

        $client->request('GET', "/{$this->dbms()}/update/{$object->id}/bar");
        self::assertResponseIsSuccessful();
        self::assertSame('bar', $object->getProp1());
    }

    #[Test]
    #[Depends('it_can_refresh_twice_using_http_client')]
    public function tracker_is_empty_after_test(): void
    {
        self::assertSame(0, PersistedObjectsTracker::countObjects());
    }

    #[Test]
    public function it_can_refresh_the_objects_after_kernel_shutdown(): void
    {
        $object = $this->factory()->create();
        self::assertSame('default1', $object->getProp1());

        self::ensureKernelShutdown();
        $client = self::createClient();

        $client->request('GET', "/{$this->dbms()}/update/{$object->id}/foo");
        self::assertResponseIsSuccessful();
        self::assertSame('foo', $object->getProp1());
    }

    #[Test]
    public function it_can_refresh_readonly_object(): void
    {
        $object = $this->objectWithReadonlyFactory()->create([
            'prop' => 1,
            'embedded' => new Embeddable('value1'),
            'date' => new \DateTimeImmutable(),
        ]);
        $objectId = $object->id;

        self::getContainer()->get('services_resetter')->reset(); // @phpstan-ignore method.notFound
        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $this->updateObject($objectId);

        self::assertSame(1, $object->prop);
    }

    #[Test]
    #[TestWith(['deleteDirectlyInDb' => false, 'clearOM' => true])]
    #[TestWith(['deleteDirectlyInDb' => false, 'clearOM' => false])]
    #[TestWith(['deleteDirectlyInDb' => true, 'clearOM' => true])]
    #[TestWith(['deleteDirectlyInDb' => true, 'clearOM' => false])]
    public function deleting_an_object_does_not_create_a_refresh_error(bool $deleteDirectlyInDb, bool $clearOM): void
    {
        $client = self::createClient();

        $object = $this->factory()->create();
        $prop1 = $object->getProp1();
        assert_persisted($object);

        self::assertSame('default1', $object->getProp1());
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $client->request(
            'DELETE',
            $deleteDirectlyInDb ? "/{$this->dbms()}/db/delete/{$object->id}" : "/{$this->dbms()}/delete/{$object->id}"
        );
        self::assertResponseIsSuccessful();

        if ($clearOM) {
            $this->objectManager()->clear();
        }

        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));
        self::assertSame($prop1, $object->getProp1());
        assert_not_persisted($object);
    }

    #[Test]
    public function it_can_refresh_the_same_object_multiple_times(): void
    {
        $client = self::createClient();

        $object = $this->factory()->create();

        $client->request('GET', "/{$this->dbms()}/update/{$object->id}");
        self::assertResponseIsSuccessful();

        $objectTracker = Configuration::instance()->persistedObjectsTracker;
        self::assertNotNull($objectTracker);

        $objectTracker->refresh();
        $objectTracker->refresh();
        $objectTracker->refresh();

        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));
        self::assertSame('foo', $object->getProp1());
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));
    }

    #[Test]
    public function it_can_refresh_after_command_terminated(): void
    {
        $object = $this->factory()->create();
        self::assertSame('default1', $object->getProp1());

        self::assertNotNull(self::$kernel);
        $application = new Application(self::$kernel);
        $command = $application->find('foundry:test:update-generic-model');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['dbms' => $this->dbms(), 'id' => $object->id]);

        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));
        self::assertSame('foo', $object->getProp1());
    }

    #[Test]
    public function it_can_refresh_all_objects(): void
    {
        [$object1, $object2] = $this->factory()->many(2)->create();
        $objectId1 = $object1->id;
        $objectId2 = $object2->id;

        refresh_all();

        $this->updateObject($objectId1);
        $this->updateObject($objectId2);

        self::assertSame('foo', $object1->getProp1());
        self::assertSame('foo', $object2->getProp1());
    }

    #[Test]
    public function it_can_refresh_all_objects_in_flush_after(): void
    {
        [$object1, $object2] = flush_after(fn() => $this->factory()->many(2)->create());
        $objectId1 = $object1->id;
        $objectId2 = $object2->id;

        refresh_all();

        $this->updateObject($objectId1);
        $this->updateObject($objectId2);

        self::assertSame('foo', $object1->getProp1());
        self::assertSame('foo', $object2->getProp1());
    }

    #[Test]
    #[DataProvider('provideRepositoryMethod')]
    public function it_can_refresh_objects_fetched_from_repository_decorator(string $methodName, array $params): void
    {
        $this->factory()->many(2)->create();

        $objectTracker = Configuration::instance()->persistedObjectsTracker;
        self::assertNotNull($objectTracker);
        PersistedObjectsTracker::reset();

        $objects = \call_user_func([$this->factory()::repository(), $methodName], ...$params); // @phpstan-ignore argument.type
        if (!\is_array($objects)) {
            $objects = [$objects];
        }
        self::assertGreaterThan(0, \count($objects));
        self::assertContainsOnlyInstancesOf(GenericModel::class, $objects);

        $ids = \array_column($objects, 'id');

        foreach ($ids as $id) {
            $this->updateObject($id);
        }

        refresh_all();

        foreach ($objects as $object) {
            self::assertSame('foo', $object->getProp1());
        }
    }

    public static function provideRepositoryMethod(): iterable
    {
        yield ['first', ['sortBy' => 'id']];
        yield ['last', ['sortBy' => 'id']];
        yield ['find', ['id' => ['prop1' => 'default1']]];
        yield ['findOneBy', ['criteria' => ['prop1' => 'default1']]];
        yield ['findBy', ['criteria' => ['prop1' => 'default1']]];
        yield ['findAll', []];
    }

    #[Test]
    public function it_can_refresh_object_fetched_using_find_and_an_id(): void
    {
        $id = $this->factory()->create()->id;

        $objectTracker = Configuration::instance()->persistedObjectsTracker;
        self::assertNotNull($objectTracker);
        PersistedObjectsTracker::reset();

        self::assertNull(
            $this->factory()::repository()->find(99999)
        );

        $object = $this->factory()::repository()->find($id);
        self::assertInstanceOf(GenericModel::class, $object);

        $this->updateObject($id);

        refresh_all();

        self::assertSame('foo', $object->getProp1());
    }

    #[Test]
    public function it_can_refresh_lazy_object(): void
    {
        $object = $this->factory()->create();
        $objectId = $object->id;

        self::getContainer()->get('services_resetter')->reset(); // @phpstan-ignore method.notFound
        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $this->updateObject($objectId);

        refresh($object);
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        self::assertSame('foo', $object->getProp1());
    }

    #[Test]
    public function it_can_disable_autorefresh(): void
    {
        $object = $this->factory()->withoutAutorefresh()->create();
        $objectId = $object->id;

        self::getContainer()->get('services_resetter')->reset(); // @phpstan-ignore method.notFound
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $this->updateObject($objectId);

        self::assertSame('default1', $object->getProp1());
    }

    #[Test]
    public function it_can_enable_autorefresh_when_disabled_globally(): void
    {
        self::bootKernel(['disable_auto_refresh' => true]);

        $object = $this->factory()->withAutorefresh()->create();
        $objectId = $object->id;

        self::getContainer()->get('services_resetter')->reset(); // @phpstan-ignore method.notFound
        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $this->updateObject($objectId);

        self::assertSame('foo', $object->getProp1());
    }

    #[Test]
    public function repository_method_returns_up_to_date_objects(): void
    {
        [$object1, $object2] = $this->factory()->many(2)->create();

        self::assertSame(2, PersistedObjectsTracker::countObjects());

        $this->updateObject($object1->id);
        $this->updateObject($object2->id);

        [$newObject1, $newObject2] = $this->factory()::all();

        self::assertSame(2, PersistedObjectsTracker::countObjects());

        self::assertSame($object1, $newObject1);
        self::assertSame($object2, $newObject2);

        self::assertSame('foo', $newObject1->getProp1());
        self::assertSame('foo', $newObject2->getProp1());

        self::assertSame('foo', $object1->getProp1());
        self::assertSame('foo', $object2->getProp1());
    }

    #[Test]
    public function can_flush_when_persisted_objects_are_ghost_objects(): void
    {
        $this->factory()->create();
        PersistedObjectsTracker::reset();

        $this->factory()::repository()->first();
        $this->factory()::repository()->first();

        $this->objectManager()->flush();

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function repository_method_returns_up_to_date_objects_with_readonly_props(): void
    {
        [$object1, $object2] = $this->objectWithReadonlyFactory()->many(2)->create([
            'prop' => 1,
            'embedded' => factory(Embeddable::class, ['prop1' => 'value1']),
            'date' => new \DateTimeImmutable(),
        ]);

        self::assertSame(2, PersistedObjectsTracker::countObjects());

        [$newObject1, $newObject2] = $this->objectWithReadonlyFactory()::all();

        self::assertSame(2, PersistedObjectsTracker::countObjects());

        self::assertSame($object1, $newObject1);
        self::assertSame($object2, $newObject2);
    }

    #[Test]
    public function can_shutdown_kernel_and_still_access_the_object(): void
    {
        $client = self::createClient();

        $object = $this->factory()->create(['prop1' => 'foo']);

        self::ensureKernelShutdown();

        $client->request('GET', '/hello-world');

        self::assertSame('foo', $object->getProp1());
    }

    /**
     * @return PersistentObjectFactory<GenericModel>
     */
    abstract protected static function factory(): PersistentObjectFactory;

    abstract protected function dbms(): string;

    abstract protected function updateObject(mixed $objectId): void;

    abstract protected function objectManager(): ObjectManager;

    /**
     * @return PersistentObjectFactory<DocumentWithReadonly|EntityWithReadonly>
     */
    abstract protected function objectWithReadonlyFactory(): PersistentObjectFactory;

    protected static function createKernel(array $options = []): KernelInterface
    {
        if (true === ($options['disable_auto_refresh'] ?? false)) {
            return parent::createKernel($options);
        }

        return new TestKernel('enable_auto_refresh_with_lazy_objects', debug: true);
    }
}
