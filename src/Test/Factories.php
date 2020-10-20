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

        if (!static::$booted) {
            static::bootKernel();
        }

        TestState::bootFromContainer(static::$kernel->getContainer());
        Factory::configuration()->setManagerRegistry(
            new LazyManagerRegistry(static function() {
                if (!static::$booted) {
                    static::bootKernel();
                }

                return static::$kernel->getContainer()->get('doctrine');
            })
        );

        self::ensureKernelShutdown();
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
