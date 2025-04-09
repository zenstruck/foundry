<?php

namespace Zenstruck\Foundry\Tests\Benchmark\ORM;

use Zenstruck\Foundry\Tests\Benchmark\Persistence\PersistentFactoryBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

class ContactFactoryBench extends PersistentFactoryBench
{
    protected static function factory(): ContactFactory
    {
        return ContactFactory::new();
    }
}
