<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ContactFactory extends PersistentObjectFactory
{
    public static function class(): string
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
