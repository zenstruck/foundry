<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\User;

final class UserFactory extends PersistentObjectFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->name(),
        ];
    }

    public static function class(): string
    {
        return User::class;
    }
}
