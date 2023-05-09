<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AddressFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Address::class;
    }

    protected function getDefaults(): array
    {
        return ['value' => 'Some address'];
    }
}
