<?php

namespace Zenstruck\Foundry\Tests\Fixture\Factories\Entity;

use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\GenericModelFactory;

final class EmptyConstructorFactory extends GenericModelFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return GenericEntity::class;
    }
}
