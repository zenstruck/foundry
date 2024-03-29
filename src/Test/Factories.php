<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Factories
{
    /**
     * @internal
     * @before
     */
    #[Before]
    public static function _bootFoundry(): void
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore-line
            // unit test
            Configuration::boot(UnitTestConfig::build());

            return;
        }

        // integration test
        // @phpstan-ignore-next-line
        Configuration::boot(static function() {
            if (!static::getContainer()->has('.zenstruck_foundry.configuration')) { // @phpstan-ignore-line
                throw new \LogicException('ZenstruckFoundryBundle is not enabled. Ensure it is added to your config/bundles.php.');
            }

            return static::getContainer()->get('.zenstruck_foundry.configuration'); // @phpstan-ignore-line
        });
    }

    /**
     * @internal
     * @after
     */
    #[After]
    public static function _shutdownFoundry(): void
    {
        Configuration::shutdown();
    }
}
