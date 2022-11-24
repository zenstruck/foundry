<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\User;

final class UserFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return User::class;
    }

    protected function getDefaults(): array
    {
        return [];
    }
}
