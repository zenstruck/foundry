<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Benchmark;

use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Warmup;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Test\UnitTestConfig;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object2Factory;

/**
 * Foundry's own cost, without a kernel nor a database.
 *
 * @phpstan-type Factory 'object'|'nested'
 */
#[Warmup(1)]
final class ObjectFactoryBench
{
    /**
     * @return iterable<string, array{Factory}>
     */
    public static function factories(): iterable
    {
        yield 'object' => ['object'];
        yield 'nested' => ['nested'];
    }

    /**
     * @return iterable<string, array{Factory, int}>
     */
    public static function factoriesAndCounts(): iterable
    {
        foreach (self::factories() as $name => [$factory]) {
            foreach ([10, 50] as $count) {
                yield "{$name}-{$count}" => [$factory, $count];
            }
        }
    }

    #[Before]
    public function bootFoundry(): void
    {
        Configuration::boot(UnitTestConfig::build());
    }

    /**
     * @param Factory $factory
     */
    #[DataProvider('factories')]
    public function bench_create(string $factory): void
    {
        self::factory($factory)->create();
    }

    /**
     * @param Factory $factory
     */
    #[DataProvider('factoriesAndCounts')]
    public function bench_create_many(string $factory, int $count): void
    {
        self::factory($factory)->many($count)->create();
    }

    /**
     * @param Factory $name
     *
     * @return ObjectFactory<covariant object>
     */
    private static function factory(string $name): ObjectFactory
    {
        return match ($name) {
            'object' => Object1Factory::new(),
            'nested' => Object2Factory::new(),
        };
    }
}
