<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\InMemory;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\SimpleObject;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\Persistence\disable_persisting;

/**
 * Runs in every permutation, including the one without any Doctrine bundle.
 */
#[AsInMemoryTest]
final class InMemoryStorageTest extends KernelTestCase
{
    #[Test]
    public function an_object_factory_stores_its_objects(): void
    {
        $object = factory(SimpleObject::class, ['prop1' => 'foo'])->create();

        self::assertSame([$object], self::stored(SimpleObject::class));
    }

    #[Test]
    public function an_object_created_without_persisting_is_stored(): void
    {
        $address = AddressFactory::new()->withoutPersisting()->create();

        self::assertSame([$address], AddressFactory::repository()->findAll());
    }

    #[Test]
    public function the_repository_stays_readable_with_persisting_disabled(): void
    {
        $address = AddressFactory::createOne();

        disable_persisting();

        self::assertSame([$address], AddressFactory::repository()->findAll());
    }

    #[Test]
    public function after_persist_hooks_never_run(): void
    {
        AddressFactory::new()->afterPersist(static fn() => throw new \LogicException('afterPersist must not run.'))->create();

        self::assertCount(1, AddressFactory::repository()->findAll());
    }

    #[Test]
    public function doctrine_events_can_be_disabled(): void
    {
        AddressFactory::new()->withoutDoctrineEvents()->create();

        self::assertCount(1, AddressFactory::repository()->findAll());
    }

    #[Test]
    public function value_objects_are_not_stored(): void
    {
        GenericEntityFactory::createOne(['date' => new \DateTimeImmutable()]);

        self::assertSame([], self::stored(\DateTimeImmutable::class));
    }

    #[Test]
    public function objects_cannot_be_truncated(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('cannot be truncated in in-memory tests');

        AddressFactory::truncate();
    }

    /**
     * @param class-string $class
     *
     * @return list<object>
     */
    private static function stored(string $class): array
    {
        return Configuration::instance()->inMemoryRepositoryRegistry?->get($class)->_all() ?? [];
    }
}
