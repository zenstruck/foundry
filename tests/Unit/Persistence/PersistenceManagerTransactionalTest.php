<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Persistence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\PersistenceStrategy;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;

final class PersistenceManagerTransactionalTest extends TestCase
{
    #[Test]
    public function it_runs_the_callback_once_inside_the_transaction_of_every_strategy(): void
    {
        $log = [];

        $manager = new PersistenceManager(
            [$this->strategy('orm', $log), $this->strategy('mongo', $log)],
            new ResetDatabaseManager([], []),
        );

        $result = $manager->transactional(static function() use (&$log) {
            $log[] = 'callback';

            return 'result';
        });

        self::assertSame('result', $result);
        self::assertSame(['begin mongo', 'begin orm', 'callback', 'end orm', 'end mongo'], $log);
    }

    /**
     * @param list<string> $log
     */
    private function strategy(string $name, array &$log): PersistenceStrategy
    {
        $strategy = $this->createMock(PersistenceStrategy::class);
        $strategy->expects(self::once())->method('transactional')->willReturnCallback(
            static function(callable $callback) use ($name, &$log) {
                $log[] = "begin {$name}";
                $result = $callback();
                $log[] = "end {$name}";

                return $result;
            }
        );

        return $strategy;
    }
}
