<?php

namespace Zenstruck\Foundry\Tests\Benchmark\ORM;

use Zenstruck\Foundry\Tests\Benchmark\Persistence\PersistentFactoryBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\StandardContactFactory;

class ContactFactoryBench extends PersistentFactoryBench
{
    protected static function factory(): StandardContactFactory
    {
        return StandardContactFactory::new()->noRandom();
    }
}
