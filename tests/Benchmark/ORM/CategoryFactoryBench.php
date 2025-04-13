<?php

namespace Zenstruck\Foundry\Tests\Benchmark\ORM;

use Zenstruck\Foundry\Tests\Benchmark\Persistence\PersistentFactoryBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\StandardCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\StandardContactFactory;

class CategoryFactoryBench extends PersistentFactoryBench
{
    protected static function factory(): StandardCategoryFactory
    {
        return StandardCategoryFactory::new([
            'contacts' => StandardContactFactory::new()->noRandom()->many(5),
        ])->noRandom();
    }
}
