<?php

namespace Zenstruck\Foundry\Tests\Benchmark\Mongo;

use PhpBench\Attributes\Groups;
use Zenstruck\Foundry\Tests\Benchmark\Persistence\GenericFactoryBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

class GenericDocumentFactoryBench extends GenericFactoryBench
{
    protected static function factory(): GenericDocumentFactory
    {
        return GenericDocumentFactory::new();
    }
}
