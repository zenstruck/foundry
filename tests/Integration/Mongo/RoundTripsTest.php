<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Mongo;

use Doctrine\ODM\MongoDB\APM\Command;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;

/**
 * Database round trips are what I/O costs in a test suite: they must not grow unnoticed.
 */
#[ResetDatabase]
#[RequiresEnvironmentVariable('MONGO_URL')]
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
        $logger = new CommandLogger();
        $logger->register();

        try {
            $scenario();
        } finally {
            $logger->unregister();
        }

        self::assertSame($expected, \array_map(static fn(Command $command): string => $command->getCommandName(), $logger->getAll()));
    }

    public static function scenarios(): iterable
    {
        // findAndModify: the INCREMENT id strategy of GenericModel, plus one to initialize the counter
        yield 'create' => [
            static fn() => GenericDocumentFactory::new()->create(),
            ['findAndModify', 'findAndModify', 'insert'],
        ];

        yield 'many(10): one insert for all' => [
            static fn() => GenericDocumentFactory::new()->many(10)->create(),
            [...\array_fill(0, 11, 'findAndModify'), 'insert'],
        ];
    }
}
