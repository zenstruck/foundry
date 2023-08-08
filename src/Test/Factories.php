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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\ChainManagerRegistry;
use Zenstruck\Foundry\Exception\FoundryBootException;
use Zenstruck\Foundry\Factory;

/**
 * @mixin KernelTestCase
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Factories
{
    /**
     * @internal
     * @before
     */
    public static function _setUpFactories(): void
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) {
            TestState::bootFoundryForUnitTest();

            return;
        }

        $kernel = static::createKernel();
        $kernel->boot();

        TestState::bootFromContainer($kernel->getContainer());
        Factory::configuration()->setManagerRegistry(
            new LazyManagerRegistry(static function(): ChainManagerRegistry {
                if (!static::$booted) {
                    static::bootKernel();
                }

                return TestState::initializeChainManagerRegistry(static::$kernel->getContainer());
            }
            )
        );

        $kernel->shutdown();
    }

    /**
     * @internal
     * @after
     */
    public static function _tearDownFactories(): void
    {
        try {
            Factory::configuration()->enablePersist();
        } catch (FoundryBootException) {
        }

        TestState::shutdownFoundry();
    }

    public function disablePersist(): void
    {
        Factory::configuration()->disablePersist();
    }

    public function enablePersist(): void
    {
        Factory::configuration()->enablePersist();
    }
}
