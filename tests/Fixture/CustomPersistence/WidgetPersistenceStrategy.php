<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\CustomPersistence;

use Zenstruck\Foundry\Persistence\CustomPersistenceStrategy;

final class WidgetPersistenceStrategy extends CustomPersistenceStrategy
{
    /** @var list<object> */
    public array $persisted = [];

    public function supports(string $class): bool
    {
        return Widget::class === $class;
    }

    public function persist(object $object): void
    {
        $this->persisted[] = $object;
    }

    public function findBy(string $class, array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return \array_slice(
            \array_values(\array_filter(
                $this->persisted,
                static fn(object $o) => $o instanceof $class && \array_all($criteria, static fn(mixed $v, string $f) => $o->{$f} === $v),
            )),
            $offset ?? 0,
            $limit,
        );
    }

    public function identifierFields(string $class): array
    {
        return ['id'];
    }
}
