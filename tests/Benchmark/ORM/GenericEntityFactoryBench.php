<?php

namespace Zenstruck\Foundry\Tests\Benchmark\ORM;

use PhpBench\Attributes\Groups;
use Zenstruck\Foundry\Tests\Benchmark\Persistence\GenericFactoryBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

class GenericEntityFactoryBench extends GenericFactoryBench
{
    protected static function factory(): GenericEntityFactory
    {
        return GenericEntityFactory::new();
    }
}
