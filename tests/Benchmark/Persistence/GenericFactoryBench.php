<?php

namespace Zenstruck\Foundry\Tests\Benchmark\Persistence;

use PhpBench\Attributes\BeforeClassMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Zenstruck\Foundry\Tests\Benchmark\KernelBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\GenericModelFactory;

#[BeforeClassMethods(['_resetDatabaseBeforeFirstBench'])]
#[BeforeMethods(['_bootFoundry', '_resetDatabaseBeforeEachBench'])]
#[Warmup(1)]
#[Revs(100)]
abstract class GenericFactoryBench extends KernelBench
{
    #[ParamProviders('_param_bench_random')]
    #[BeforeMethods('_setup_bench_random')]
    public function bench_random(): void
    {
        mt_srand(1);
        static::factory()::random();
    }

    #[ParamProviders('_param_bench_random')]
    #[BeforeMethods('_setup_bench_random')]
    public function bench_random_set(array $params): void
    {
        mt_srand(1);
        static::factory()::randomSet((int)(ceil($params['count'] / 10))); // @phpstan-ignore argument.type
    }

    public function _setup_bench_random(array $params): void
    {
        static::factory()->many($params['count'])->create();
    }

    public function _param_bench_random(): array
    {
        return [
            '50' => ['count' => 50],
            '100' => ['count' => 100],
            '500' => ['count' => 500]
        ];
    }

    abstract protected static function factory(): GenericModelFactory;
}
