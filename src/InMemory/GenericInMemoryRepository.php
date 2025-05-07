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
 * @implements InMemoryRepository<T>
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @experimental
 *
 * This class will be used when a specific "in-memory" repository does not exist for a given class.
 */
final class GenericInMemoryRepository implements InMemoryRepository
{
    /**
     * @var list<T>
     */
    private array $elements = [];

    /**
     * @param class-string<T> $class
     */
    public function __construct(
        private readonly string $class,
    ) {
    }

    /**
     * @param T $item
     */
    public function _save(object $item): void
    {
        if (!$item instanceof $this->class) {
            throw new \InvalidArgumentException(\sprintf('Given object of class "%s" is not an instance of expected "%s"', $item::class, $this->class));
        }

        if (!\in_array($item, $this->elements, true)) {
            $this->elements[] = $item;
        }
    }

    public function _all(): array
    {
        return $this->elements;
    }

    public static function _class(): string
    {
        throw new \BadMethodCallException('This method should not be called on a GenericInMemoryRepository.');
    }
}
