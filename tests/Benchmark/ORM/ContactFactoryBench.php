<?php

namespace Zenstruck\Foundry\Tests\Benchmark\ORM;

use PhpBench\Attributes\Revs;
use Zenstruck\Foundry\Tests\Benchmark\Persistence\PersistentFactoryBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\StandardContactFactory;

class ContactFactoryBench extends PersistentFactoryBench
{
    #[Revs(100)]
    public function bench_create(): void
    {
        static::factory()->create();
    }

    protected static function factory(): StandardContactFactory
    {
        return StandardContactFactory::new()->noRandom();
    }
}
