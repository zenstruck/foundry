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

namespace Zenstruck\Foundry\InMemory;

/**
 * @template T of object
 * @phpstan-require-implements InMemoryRepository
 * @experimental
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
trait InMemoryRepositoryTrait
{
    /**
     * @var list<T>
     */
    private array $items = [];

    /**
     * @param T $item
     */
    public function _save(object $item): void
    {
        if (!\is_a($item, self::_class(), allow_string: true)) {
            throw new \InvalidArgumentException(\sprintf('Given object of class "%s" is not an instance of expected "%s"', $item::class, self::_class()));
        }

        if (!\in_array($item, $this->items, true)) {
            $this->items[] = $item;
        }
    }

    /**
     * @return list<T>
     */
    public function _all(): array
    {
        return $this->items;
    }
}
