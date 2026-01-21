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
trait Factories
{
    /**
     * @internal
     * @before
     */
    #[Before(5)]
    public function _beforeHook(): void
    {
        if (FoundryExtension::isEnabled()) {
            trigger_deprecation('zenstruck/foundry', '2.9', \sprintf('Trait %s is deprecated and will be removed in Foundry 3. See https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.9.md to upgrade.', Factories::class));

            return;
        }

        if (FoundryExtension::shouldBeEnabled()) {
            trigger_deprecation('zenstruck/foundry', '2.9', 'Not using Foundry\'s PHPUnit extension is deprecated and will throw an error in Foundry 3. See https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.9.md to upgrade.');
        }

        $this->_bootFoundry();
    }

    /**
     * @internal
     * @after
     */
    #[After(5)]
    public static function _shutdownFoundry(): void
    {
        if (FoundryExtension::isEnabled()) {
            return;
        }

        Configuration::shutdown();
    }

    /**
     * @internal
     */
    private function _bootFoundry(): void
    {
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
}
