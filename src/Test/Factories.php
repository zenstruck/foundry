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

use function Zenstruck\Foundry\Persistence\disable_persisting;
use function Zenstruck\Foundry\Persistence\enable_persisting;

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
            $configuration = Factory::configuration();

            if ($configuration->hasManagerRegistry()) {
                $configuration->enablePersist();
            }
        } catch (FoundryBootException) {
        }

        TestState::shutdownFoundry();
    }

    /**
     * @deprecated
     */
    public function disablePersist(): void
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in Foundry 2.0. Use "Zenstruck\Foundry\Persistence\disable_persisting()" instead.', __METHOD__);

        disable_persisting();
    }

    /**
     * @deprecated
     */
    public function enablePersist(): void
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in Foundry 2.0. Use "Zenstruck\Foundry\Persistence\enable_persisting()" instead.', __METHOD__);

        enable_persisting();
    }
}
