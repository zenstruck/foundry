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
    #[Test]
    public function it_commits_each_connection_once(): void
    {
        $shared = $this->connection(commits: 1);
        $other = $this->connection(commits: 1);

        $strategy = $this->strategy($shared, $shared, $other);

        self::assertSame('result', $strategy->transactional(static fn() => 'result'));
    }

    #[Test]
    public function it_rolls_back_when_the_callback_fails(): void
    {
        $connection = $this->connection(commits: 0, rollBacks: 1);

        $this->expectExceptionObject(new \RuntimeException('story failed'));

        $this->strategy($connection)->transactional(static fn() => throw new \RuntimeException('story failed'));
    }

    #[Test]
    public function it_keeps_the_original_error_when_the_rollback_fails(): void
    {
        $connection = $this->connection(commits: 0);
        $connection->method('rollBack')->willThrowException(new \LogicException('rollback failed'));

        try {
            $this->strategy($connection)->transactional(static fn() => throw new \RuntimeException('story failed'));
        } catch (\LogicException $e) {
        }

        self::assertSame('rollback failed', $e->getMessage());
        self::assertSame('story failed', $e->getPrevious()?->getMessage());
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

    private function connection(int $commits, int $rollBacks = 0): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::exactly($commits))->method('commit');
        $connection->method('isTransactionActive')->willReturn(true);

        if ($rollBacks > 0) {
            $connection->expects(self::exactly($rollBacks))->method('rollBack');
        }

        return $connection;
    }
}
