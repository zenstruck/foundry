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

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\ORM\OrmV3PersistenceStrategy;

final class OrmTransactionalTest extends TestCase
{
    /**
     * @test
     */
    #[Test]
    public function it_commits_each_connection_once(): void
    {
        $shared = $this->connection(commits: 1);
        $other = $this->connection(commits: 1);

        $strategy = $this->strategy($shared, $shared, $other);

        self::assertSame('result', $strategy->transactional(static fn() => 'result'));
    }

    /**
     * @test
     */
    #[Test]
    public function it_rolls_back_when_the_callback_fails(): void
    {
        $connection = $this->connection(commits: 0, rollBacks: 1);

        $this->expectExceptionObject(new \RuntimeException('story failed'));

        $this->strategy($connection)->transactional(static fn() => throw new \RuntimeException('story failed'));
    }

    /**
     * @test
     */
    #[Test]
    public function it_keeps_the_original_error_when_the_rollback_fails(): void
    {
        $connection = $this->connection(commits: 0);
        $connection->method('rollBack')->willThrowException(new \LogicException('rollback failed'));

        try {
            $this->strategy($connection)->transactional(self::failingStory());
            self::fail('The rollback error should have been thrown.');
        } catch (\LogicException $e) {
        }

        self::assertSame('rollback failed', $e->getMessage());
        self::assertSame('story failed', $e->getPrevious()?->getMessage());
    }

    /**
     * @test
     */
    #[Test]
    public function it_only_rolls_back_the_transactions_it_still_has_open(): void
    {
        // both connections are already in an outer transaction (eg: DAMA), so they stay
        // active once their own level is committed
        $committed = $this->connection(commits: 1, rollBacks: 0);
        $failing = $this->connection(commits: 1, rollBacks: 1);
        $failing->method('commit')->willThrowException(new \RuntimeException('commit failed'));

        $this->expectExceptionObject(new \RuntimeException('commit failed'));

        $this->strategy($committed, $failing)->transactional(static fn() => 'result');
    }

    /**
     * Typed as returning, so that static analysis does not consider the code after the call dead.
     *
     * @return callable():string
     */
    private static function failingStory(): callable
    {
        return static fn() => throw new \RuntimeException('story failed');
    }

    private function strategy(Connection ...$connections): OrmV3PersistenceStrategy
    {
        $managers = [];
        foreach ($connections as $connection) {
            $manager = $this->createStub(EntityManagerInterface::class);
            $manager->method('getConnection')->willReturn($connection);
            $managers[] = $manager;
        }

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagers')->willReturn($managers);

        return new OrmV3PersistenceStrategy($registry);
    }

    private function connection(int $commits, ?int $rollBacks = null): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::exactly($commits))->method('commit');

        if (null !== $rollBacks) {
            $connection->expects(self::exactly($rollBacks))->method('rollBack');
        }

        return $connection;
    }
}
