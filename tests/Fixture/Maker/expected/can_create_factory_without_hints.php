<?php

namespace App\Factory;

use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Object1;

/**
 * @extends ObjectFactory<Object1>
 */
final class Object1Factory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Object1::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'prop1' => self::faker()->sentence(),
            'prop2' => self::faker()->sentence(),
            'prop3' => self::faker()->sentence(),
        ];
    }
}
