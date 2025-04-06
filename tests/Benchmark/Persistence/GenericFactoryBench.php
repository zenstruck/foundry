<?php

namespace Zenstruck\Foundry\Tests\Benchmark\Persistence;

use PhpBench\Attributes\BeforeClassMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\ParamProviders;
use Zenstruck\Foundry\Benchmark\KernelBench;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

#[BeforeClassMethods(['_resetDatabaseBeforeFirstBench'])]
#[BeforeMethods(['_bootFoundry', '_resetDatabaseBeforeEachBench'])]
abstract class GenericFactoryBench extends KernelBench
{
    #[ParamProviders('_param_bench_random')]
    #[BeforeMethods('_setup_bench_random')]
    public function bench_random(): void
    {
        static::factory()::random();
    }

    public function _param_bench_random(): array
    {
        return [
            '1' => ['count' => 1],
            '50' => ['count' => 50],
            '500' => ['count' => 500],
            '1000' => ['count' => 1000]
        ];
    }

    public function _setup_bench_random(array $params): void
    {
        static::factory()->many($params['count'])->create();
    }

    abstract protected static function factory(): GenericEntityFactory;
}
