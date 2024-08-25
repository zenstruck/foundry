<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\InMemory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @template T of object
 */
interface InMemoryRepository
{
    /**
     * @param T $element
     */
    public function _save(object $element): void;

    /**
     * @return list<T>
     */
    public function _all(): array;
}
