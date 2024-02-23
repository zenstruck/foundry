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

namespace Zenstruck\Foundry\Maker\Factory\Exception;

final class FactoryClassAlreadyExistException extends \InvalidArgumentException
{
    public function __construct(string $factoryClass)
    {
        parent::__construct("Factory \"{$factoryClass}\" already exists.");
    }
}
