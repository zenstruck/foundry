<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Persistence;

use Faker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\FactoryRegistry;
use Zenstruck\Foundry\FakerAdapter;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\CustomPersistenceStrategy;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\StoryRegistry;

final class CustomPersistenceStrategyTest extends TestCase
{
    protected function tearDown(): void
    {
        Configuration::shutdown();
    }

    #[Test]
    public function it_finds_by_a_bare_identifier_through_the_declared_fields(): void
    {
        $manager = new PersistenceManager([$strategy = new InMemoryArrayStrategy()], new ResetDatabaseManager([], []));
        Configuration::boot(new Configuration(
            new FactoryRegistry([]),
            new FakerAdapter(Faker\Factory::create()),
            Instantiator::withConstructor(),
            new StoryRegistry([]),
            persistence: $manager,
        ));
        $strategy->persist($seven = new Thing(7, 'seven'));

        self::assertSame(['id'], $manager->getIdentifierFields(Thing::class));
        self::assertSame($seven, RepositoryDecorator::for(Thing::class)->find(7));
    }

    #[Test]
    public function it_derives_identifier_values_from_the_declared_fields(): void
    {
        $strategy = new InMemoryArrayStrategy();

        self::assertSame(['id' => 7], $strategy->getIdentifierValues(new Thing(7, 'seven')));
    }

    #[Test]
    public function it_has_no_identifier_values_until_every_identity_field_is_set(): void
    {
        $strategy = new InMemoryArrayStrategy();

        self::assertSame([], $strategy->getIdentifierValues(new Thing(null, 'new')));
        self::assertSame([], $strategy->getIdentifierValues((new \ReflectionClass(Thing::class))->newInstanceWithoutConstructor()));
    }

    #[Test]
    public function a_backend_buffering_until_flush_all_writes_on_every_flush(): void
    {
        $manager = new PersistenceManager([$strategy = new BufferingStrategy()], new ResetDatabaseManager([], []));

        $manager->save(new Thing(1, 'one'));
        self::assertCount(1, $strategy->written);

        $manager->flushAfter(static function() use ($manager, $strategy) {
            $manager->save(new Thing(2, 'two'));
            $manager->save(new Thing(3, 'three'));

            self::assertCount(1, $strategy->written);
        });
        self::assertCount(3, $strategy->written);
    }

    #[Test]
    public function it_finds_through_find_by(): void
    {
        $strategy = new InMemoryArrayStrategy();
        $strategy->persist($seven = new Thing(7, 'seven'));
        $strategy->persist(new Thing(8, 'eight'));

        self::assertSame($seven, $strategy->find(Thing::class, ['id' => 7]));
        self::assertNull($strategy->find(Thing::class, ['id' => 99]));
    }

    #[Test]
    public function it_supports_neither_deletion_nor_truncation_by_default(): void
    {
        $strategy = new InMemoryArrayStrategy();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('cannot delete objects');

        $strategy->remove(new Thing(1, 'one'));
    }

    #[Test]
    public function it_has_no_lifecycle_events_to_disable(): void
    {
        $strategy = new InMemoryArrayStrategy();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('no lifecycle events to disable');

        $strategy->disablePersistenceEvents(Thing::class, []);
    }

    #[Test]
    public function it_opts_out_of_auto_refresh(): void
    {
        self::assertFalse((new InMemoryArrayStrategy())->supportsAutoRefresh());
    }
}

final class Thing
{
    public function __construct(public ?int $id, public string $name)
    {
    }
}

/**
 * The smallest possible backend: four methods, no Doctrine.
 */
final class InMemoryArrayStrategy extends CustomPersistenceStrategy
{
    /** @var list<object> */
    private array $objects = [];

    public function supports(string $class): bool
    {
        return Thing::class === $class;
    }

    public function persist(object $object): void
    {
        $this->objects[] = $object;
    }

    public function findBy(string $class, array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $matching = \array_values(\array_filter(
            $this->objects,
            static fn(object $o) => $o instanceof $class
                && \array_all($criteria, static fn(mixed $v, string $f) => $o->{$f} === $v),
        ));

        return \array_slice($matching, $offset ?? 0, $limit);
    }

    public function identifierFields(string $class): array
    {
        return ['id'];
    }
}

/**
 * Sends one batch per flush, and overrides nothing but flushAll() to do so.
 */
final class BufferingStrategy extends CustomPersistenceStrategy
{
    /** @var list<object> */
    public array $written = [];

    /** @var list<object> */
    private array $buffer = [];

    public function supports(string $class): bool
    {
        return Thing::class === $class;
    }

    public function persist(object $object): void
    {
        $this->buffer[] = $object;
    }

    public function flushAll(): void
    {
        $this->written = [...$this->written, ...$this->buffer];
        $this->buffer = [];
    }

    public function findBy(string $class, array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return [];
    }

    public function identifierFields(string $class): array
    {
        return ['id'];
    }
}
