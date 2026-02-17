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
use Zenstruck\Foundry\PHPUnit\FoundryExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
if (!\method_exists(Before::class, '__construct')) { // @phpstan-ignore function.alreadyNarrowedType
    trait Factories
    {
        use CommonFactories;

        /**
         * @internal
         * @before
         */
        #[Before]
        public function _beforeHook(): void
        {
            $this->_bootFoundry();
        }

        /**
         * @internal
         * @after
         */
        #[After]
        public static function _afterHook(): void
        {
            self::_shutdownFoundry();
        }
    }
} else {
    trait Factories
    {
        use CommonFactories;

        /** @internal */
        #[Before(5)]
        public function _beforeHook(): void
        {
            $this->_bootFoundry();
        }

        /** @internal */
        #[After(5)]
        public static function _afterHook(): void
        {
            self::_shutdownFoundry();
        }
    }
}

/** @internal */
trait CommonFactories
{
    /** @internal */
    private function _bootFoundry(): void
    {
        if (FoundryExtension::isEnabled()) {
            trigger_deprecation('zenstruck/foundry', '2.9', \sprintf('Trait %s is deprecated and will be removed in Foundry 3. See https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.9.md to upgrade.', Factories::class));

            return;
        }

        if (FoundryExtension::shouldBeEnabled()) {
            trigger_deprecation('zenstruck/foundry', '2.9', 'Not using Foundry\'s PHPUnit extension is deprecated and will throw an error in Foundry 3. See https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.9.md to upgrade.');
        }

        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore function.impossibleType, function.alreadyNarrowedType
            // unit test
            Configuration::boot(UnitTestConfig::build());

            return;
        }

        // integration test
        Configuration::boot(static function(): Configuration {
            if (!static::getContainer()->has('.zenstruck_foundry.configuration')) { // @phpstan-ignore staticMethod.notFound
                throw new \LogicException('ZenstruckFoundryBundle is not enabled. Ensure it is added to your config/bundles.php.');
            }

            return static::getContainer()->get('.zenstruck_foundry.configuration'); // @phpstan-ignore staticMethod.notFound, return.type
        });
    }

    /** @internal */
    private static function _shutdownFoundry(): void
    {
        if (FoundryExtension::isEnabled()) {
            return;
        }

        Configuration::shutdown();
    }
}
