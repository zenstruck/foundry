<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @internal
 */
interface FactoryRegistryInterface
{
    /**
     * @template T of Factory
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function get(string $class): Factory;
}
