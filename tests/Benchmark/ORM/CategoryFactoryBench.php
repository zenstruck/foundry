<?php

namespace Zenstruck\Foundry\Tests\Benchmark\ORM;

use PhpBench\Attributes\Revs;
use Zenstruck\Foundry\Tests\Benchmark\Persistence\PersistentFactoryBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\StandardCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\StandardContactFactory;

class CategoryFactoryBench extends PersistentFactoryBench
{
    #[Revs(10)]
    public function bench_create(): void
    {
        static::factory()->create();
    }
    protected static function factory(): StandardCategoryFactory
    {
        return StandardCategoryFactory::new([
            'contacts' => StandardContactFactory::new()->noRandom()->many(5),
        ])->noRandom();
    }
}
