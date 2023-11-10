<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Test;

use Faker;
use Zenstruck\Foundry\Instantiator;

final class UnitTestConfig
{
    public static function configure(?Instantiator $instantiator = null, ?Faker\Generator $faker = null): void
    {
        TestState::configureInternal($instantiator, $faker);
    }
}
