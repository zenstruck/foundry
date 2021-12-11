<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ContactFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return Contact::class;
    }

    protected function getDefaults(): array
    {
        return [
            'name' => 'Sally',
            'address' => AddressFactory::new(),
        ];
    }
}
