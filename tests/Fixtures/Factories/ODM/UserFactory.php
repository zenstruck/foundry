<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;

final class UserFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ODMUser::class;
    }

    protected function getDefaults(): array
    {
        return [];
    }
}
