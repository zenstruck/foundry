<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories;

use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\SimpleObject;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @extends ObjectFactory<SimpleObject>
 */
final class SimpleObjectFactory extends ObjectFactory
{
    public static function class(): string
    {
        return SimpleObject::class;
    }

    public function withProps(string $prop1, ?string $prop2 = null): static
    {
        return $this->with([
            'prop1' => $prop1,
            'prop2' => $prop2,
        ]);
    }

    protected function defaults(): array
    {
        return [
            'prop1' => self::faker()->word(),
        ];
    }
}
