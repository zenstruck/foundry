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

namespace Zenstruck\Foundry\Tests\Integration\ForceFactoriesTraitUsage;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\Proxy;

abstract class AnotherBaseTestCase extends KernelTestCase
{
    // @phpstan-ignore missingType.generics
    public function useProxyClass(Proxy $proxy): void
    {
        $proxy->_real();
    }
}
