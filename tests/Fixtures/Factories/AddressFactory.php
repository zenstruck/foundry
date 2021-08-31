<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AddressFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return Address::class;
    }

    protected function getDefaults(): array
    {
        return ['value' => 'Some address'];
    }
}
