<?php

namespace Zenstruck\Foundry\Tests\Benchmark\Mongo;

use Zenstruck\Foundry\Tests\Benchmark\Persistence\PersistentFactoryBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;

class PersistentDocumentFactoryBench extends PersistentFactoryBench
{
    protected static function factory(): GenericDocumentFactory
    {
        return GenericDocumentFactory::new();
    }
}
