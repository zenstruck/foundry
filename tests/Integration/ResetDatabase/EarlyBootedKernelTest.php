<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Middleware;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\DoctrineMiddleware;

/**
 * @requires PHPUnit >=11.3
 */
#[RequiresPhpunit('>=11.3')]
final class EarlyBootedKernelTest extends ResetDatabaseTestCase
{
    /**
     * Needs to happen before {@see ResetDatabase::_resetDatabaseBeforeFirstTest()}.
     */
    #[BeforeClass(10)]
    public static function before(): void
    {
        self::bootKernel();
    }

    #[Test]
    public function connection_uses_doctrine_middleware(): void
    {
        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);

        self::assertContains(
            DoctrineMiddleware::class,
            \array_map(static fn(Middleware $middleware) => $middleware::class, $connection->getConfiguration()->getMiddlewares())
        );
    }
}
