<?php

namespace Zenstruck\Foundry\Tests\Benchmark\ORM;

use Zenstruck\Foundry\Tests\Benchmark\Persistence\PersistentFactoryBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;

class CategoryFactoryBench extends PersistentFactoryBench
{
    protected static function factory(): CategoryFactory
    {
        return CategoryFactory::new([
            'contacts' => ContactFactory::new()->many(5),
        ]);
    }
}
