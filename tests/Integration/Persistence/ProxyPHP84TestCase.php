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

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy\PersistedObjectsTracker;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;

use function Zenstruck\Foundry\Persistence\assert_not_persisted;
use function Zenstruck\Foundry\Persistence\assert_persisted;
use function Zenstruck\Foundry\Persistence\refresh_all;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12
 */
#[RequiresPhpunit('>=12')]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '1')]
abstract class ProxyPHP84TestCase extends WebTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
    public function it_can_refresh_after_services_reset(): void
    {
        $object = $this->factory()->create();
        self::ensureKernelShutdown();

        $this->updateObject($object);

        self::assertSame('default1', $object->getProp1());

        self::getContainer()->get('services_resetter')->reset(); // @phpstan-ignore method.notFound

        self::assertSame('foo', $object->getProp1());
    }

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
    public function it_can_refresh_objects_with_php84_proxies(): void
    {
        $object = $this->factory()->create();
        self::assertSame('default1', $object->getProp1());
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        self::ensureKernelShutdown();

        $client = self::createClient();
        $client->request('GET', "/{$this->dbms()}/update/{$object->id}");
        self::assertResponseIsSuccessful();

        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));
        assert_persisted($object);
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
        self::assertSame(0, PersistedObjectsTracker::countObjects());
    }

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
    public function deleting_an_object_does_not_create_a_refresh_error(): void
    {
        $object = $this->factory()->create();
        assert_persisted($object);

        self::assertSame('default1', $object->getProp1());
        self::assertFalse((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        self::ensureKernelShutdown();

        $client = self::createClient();
        $client->request('GET', "/{$this->dbms()}/delete/{$object->id}");
        self::assertResponseIsSuccessful();

        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));
        assert_not_persisted($object);
    }

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
    public function it_can_refresh_the_same_object_multiple_times(): void
    {
        $object = $this->factory()->create();
        self::ensureKernelShutdown();

        $client = self::createClient();
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

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
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

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>= 8.4')]
    public function it_can_refresh_all_objects(): void
    {
        [$object1, $object2] = $this->factory()->many(2)->create();

        self::ensureKernelShutdown();

        $this->updateObject($object1);
        $this->updateObject($object2);

        self::assertSame('default1', $object1->getProp1());
        self::assertSame('default1', $object2->getProp1());

        refresh_all();

        self::assertSame('foo', $object1->getProp1());
        self::assertSame('foo', $object2->getProp1());
    }

    /**
     * @return PersistentObjectFactory<GenericModel>
     */
    abstract protected static function factory(): PersistentObjectFactory;

    abstract protected function dbms(): string;

    abstract protected function updateObject(GenericModel $object): void;

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel('enable_auto_refresh_with_lazy_objects', debug: true);
    }
}
