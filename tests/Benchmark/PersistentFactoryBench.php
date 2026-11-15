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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Warmup;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;

/**
 * The factories are data sets rather than subclasses: Tally keeps the expected numbers
 * on the method that declares a benchmark, so an inherited benchmark cannot have one per subclass.
 *
 * @phpstan-type Factory 'contact'|'category'|'document'
 */
#[Warmup(1)]
final class PersistentFactoryBench
{
    /**
     * @return iterable<string, array{Factory}>
     */
    public static function factories(): iterable
    {
        if (\getenv('DATABASE_URL')) {
            yield 'contact' => ['contact'];
            yield 'category' => ['category'];
        }

        if (\getenv('MONGO_URL')) {
            yield 'document' => ['document'];
        }
    }

    /**
     * @return iterable<string, array{Factory, int}>
     */
    public static function factoriesAndCounts(): iterable
    {
        foreach (self::factories() as $name => [$factory]) {
            foreach ([1, 10, 50] as $count) {
                yield "{$name}-{$count}" => [$factory, $count];
            }
        }
    }

    /**
     * Each case runs in a process of its own, so this is always the first "test".
     */
    #[Before]
    public function bootFoundry(): void
    {
        $kernel = new TestKernel('test', false);
        $kernel->boot();
        ResetDatabaseManager::resetBeforeFirstTest($kernel);
        $kernel->shutdown();

        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');
        \assert($container instanceof ContainerInterface);
        Configuration::boot(static fn(): Configuration => $container->get('.zenstruck_foundry.configuration')); // @phpstan-ignore return.type
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
     * @return PersistentObjectFactory<covariant object>
     */
    private static function factory(string $name): PersistentObjectFactory
    {
        return match ($name) {
            'contact' => ContactFactory::new()->noRandom(),
            'category' => CategoryFactory::new(['contacts' => ContactFactory::new()->many(5)])->noRandom(),
            'document' => GenericDocumentFactory::new(),
        };
    }
}
