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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Persistence\CustomPersistenceStrategy;

final class CustomPersistenceStrategyTest extends TestCase
{
    #[Test]
    public function it_derives_identifier_values_from_the_declared_fields(): void
    {
        $strategy = new InMemoryArrayStrategy();

        self::assertSame(['id' => 7], $strategy->getIdentifierValues(new Thing(7, 'seven')));
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
    public function __construct(public int $id, public string $name)
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

    protected function identifierFields(string $class): array
    {
        return ['id'];
    }
}
