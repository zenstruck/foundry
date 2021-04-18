<?php

namespace Zenstruck\Foundry\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
            TestState::bootFoundry();

            return;
        }

        $kernel = static::createKernel();
        $kernel->boot();

        TestState::bootFromContainer($kernel->getContainer());
        Factory::configuration()->setManagerRegistry(
            new LazyManagerRegistry(static function() {
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
        TestState::shutdownFoundry();
    }
}
