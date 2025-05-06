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
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @template T of object
 * @experimental
 */
interface InMemoryRepository
{
    /**
     * @return class-string<T>
     */
    public static function _class(): string;

    /**
     * @param T $item
     */
    public function _save(object $item): void;

    /**
     * @return list<T>
     */
    public function _all(): array;
}
