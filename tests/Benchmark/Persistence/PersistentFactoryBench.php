<?php

namespace Zenstruck\Foundry\Tests\Benchmark\Persistence;

use PhpBench\Attributes\BeforeClassMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Benchmark\KernelBench;

#[BeforeClassMethods(['_resetDatabaseBeforeFirstBench'])]
#[BeforeMethods(['_bootFoundry', '_resetDatabaseBeforeEachBench'])]
#[Warmup(1)]
#[Revs(10)]
abstract class PersistentFactoryBench extends KernelBench
{
    public function bench_create(): void
    {
        static::factory()->create();
    }

    #[ParamProviders('_param_bench_many')]
    public function bench_create_many(array $params): void
    {
        static::factory()->many($params['count'])->create();
    }

    public function _param_bench_many(): array
    {
        return [
            '1' => ['count' => 1],
            '10' => ['count' => 10],
            '50' => ['count' => 50]
        ];
    }

    /** @return PersistentObjectFactory<object> */
    abstract protected static function factory(): PersistentObjectFactory;
}
