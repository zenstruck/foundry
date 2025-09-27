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

namespace Zenstruck\Foundry\Tests\Fixture\ResetDatabase;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Symfony\Component\HttpKernel\KernelInterface;

final class DoctrineMiddleware implements Middleware
{
    public function __construct(
        private KernelInterface $kernel, // @phpstan-ignore property.onlyWritten
    ) {
    }

    public function wrap(Driver $driver): Driver
    {
        return $driver;
    }
}
