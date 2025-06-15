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

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\ObjectFactory;

final class DummyObjectFactory extends ObjectFactory
{
    public static function class(): string
    {
        return DummyObject::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}
