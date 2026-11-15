<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;

/**
 * Database round trips are what I/O costs in a test suite: they must not grow unnoticed.
 */
#[ResetDatabase]
#[RequiresEnvironmentVariable('DATABASE_URL')]
final class RoundTripsTest extends KernelTestCase
{
    /**
     * @param callable():mixed $scenario
     * @param list<string>     $expected
     */
    #[Test]
    #[DataProvider('scenarios')]
    public function round_trips(callable $scenario, array $expected): void
    {
        $holder = self::getContainer()->get('doctrine.debug_data_holder');
        \assert($holder instanceof DebugDataHolder);
        $holder->reset();

        $scenario();

        self::assertSame(
            $expected,
            \array_map(static fn(array $query): string => \explode(' ', $query['sql'])[0], $holder->getData()['default'] ?? []),
        );
    }

    public static function scenarios(): iterable
    {
        yield 'create: contact, address, category' => [
            static fn() => ContactFactory::new()->create(),
            ['"START', ...\array_fill(0, 3, 'INSERT'), '"COMMIT"'],
        ];

        yield 'many(10): one flush for all' => [
            static fn() => ContactFactory::new()->many(10)->create(),
            ['"START', ...\array_fill(0, 30, 'INSERT'), '"COMMIT"'],
        ];

        yield 'one-to-many: category with 5 contacts' => [
            static fn() => CategoryFactory::new(['contacts' => ContactFactory::new()->many(5)])->create(),
            ['"START', ...\array_fill(0, 11, 'INSERT'), '"COMMIT"'],
        ];
    }
}
