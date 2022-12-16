<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional;

use Psr\Container\ContainerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ContainerBC
{
    protected static function container(): ContainerInterface
    {
        if (!\method_exists(static::class, 'getContainer')) {
            if (!static::$booted) {
                static::bootKernel();
            }

            return self::$container;
        }

        return self::getContainer();
    }
}
