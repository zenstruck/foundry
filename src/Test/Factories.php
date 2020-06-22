<?php

namespace Zenstruck\Foundry\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\StoryManager;

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
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        if (!static::$booted) {
            static::bootKernel();
        }

        TestState::bootFromContainer(static::$kernel->getContainer())->setManagerRegistry(
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
        Factory::faker()->unique(true); // reset unique
        StoryManager::reset();
    }
}
