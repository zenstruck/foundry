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

namespace Zenstruck\Foundry\Tests\Unit\Test\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;
use Zenstruck\Foundry\Test\Behat\Listener\BootConfigurationListener;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;
use Zenstruck\Foundry\Test\UnitTestConfig;

final class BootConfigurationListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        if (Configuration::isBooted()) {
            Configuration::shutdown();
        }
    }

    #[Test]
    public function it_returns_subscribed_events(): void
    {
        $events = BootConfigurationListener::getSubscribedEvents();

        self::assertArrayHasKey(ExerciseCompleted::BEFORE, $events);
        self::assertArrayHasKey(ExerciseCompleted::AFTER, $events);
        self::assertArrayHasKey(FeatureTested::BEFORE, $events);
        self::assertArrayHasKey(FeatureTested::AFTER, $events);
        self::assertArrayHasKey(ScenarioTested::BEFORE, $events);
        self::assertArrayHasKey(ExampleTested::BEFORE, $events);

        self::assertSame(['bootFoundry', 100], $events[ExerciseCompleted::BEFORE]);
        self::assertSame(['shutdownFoundry', -100], $events[ExerciseCompleted::AFTER]);
        self::assertSame(['bootFoundry', 100], $events[FeatureTested::BEFORE]);
        self::assertSame(['shutdownFoundryAfterFeature', -100], $events[FeatureTested::AFTER]);
        self::assertSame(['bootFoundry', 100], $events[ScenarioTested::BEFORE]);
        self::assertSame(['bootFoundry', 100], $events[ExampleTested::BEFORE]);
    }

    #[Test]
    public function it_boots_foundry_when_not_already_booted(): void
    {
        self::assertFalse(Configuration::isBooted());

        $listener = $this->createListenerWithMockedKernel();
        $listener->bootFoundry();

        self::assertTrue(Configuration::isBooted());
    }

    #[Test]
    public function it_shuts_down_foundry(): void
    {
        Configuration::boot(UnitTestConfig::build());
        self::assertTrue(Configuration::isBooted());

        $listener = $this->createListenerWithMockedKernel();
        $listener->shutdownFoundry();

        self::assertFalse(Configuration::isBooted());
    }

    #[Test]
    public function it_shuts_down_foundry_after_feature_and_resets_registry(): void
    {
        Configuration::boot(UnitTestConfig::build());
        $registry = $this->createRegistry();
        $testObj = new TestEntity(1);
        $registry->store($testObj, 'test-object');

        self::assertTrue($registry->isStored($testObj));
        self::assertTrue(Configuration::isBooted());

        $listener = $this->createListenerWithRegistry($registry);
        $listener->shutdownFoundryAfterFeature();

        self::assertFalse($registry->isStored($testObj));
        self::assertFalse(Configuration::isBooted());
    }

    private function createListenerWithMockedKernel(): BootConfigurationListener
    {
        $config = UnitTestConfig::build();

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('.zenstruck_foundry.configuration')
            ->willReturn($config);

        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getContainer')->willReturn($container);

        return new BootConfigurationListener($kernel);
    }

    private function createListenerWithRegistry(ObjectRegistry $registry): BootConfigurationListener
    {
        $config = UnitTestConfig::build();

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(static fn(string $id) => match ($id) {
                '.zenstruck_foundry.configuration' => $config,
                '.zenstruck_foundry.behat.object_registry' => $registry,
                default => throw new \RuntimeException("Unexpected service: {$id}"),
            });

        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getContainer')->willReturn($container);

        return new BootConfigurationListener($kernel);
    }

    private function createRegistry(): ObjectRegistry
    {
        $resolver = new FactoryShortNameResolver([new TestEntityFactory()]);

        return new ObjectRegistry($resolver, $this->createStub(PersistenceManager::class));
    }
}

final class TestEntity
{
    public function __construct(
        public int $id,
    ) {
    }
}

/** @extends ObjectFactory<TestEntity> */
final class TestEntityFactory extends ObjectFactory
{
    public static function class(): string
    {
        return TestEntity::class;
    }

    protected function defaults(): array
    {
        return [
            'id' => 1,
        ];
    }
}
