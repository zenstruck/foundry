<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Implemented by strategies able to describe their classes. Consumed by the maker and the Behat
 * integration, never on the object creation path.
 *
 * @internal
 */
interface ProvidesMetadata
{
    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return ClassMetadata<T>
     */
    public function classMetadata(string $class): ClassMetadata;

    /**
     * @return iterable<ClassMetadata<object>>
     */
    public function allMetadata(): iterable;
}
