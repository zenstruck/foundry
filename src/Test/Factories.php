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

use function Zenstruck\Foundry\Persistence\initialize_proxy_object;

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
            trigger_deprecation('zenstruck/foundry', '2.8', sprintf('Trait %s is deprecated and will be removed in Foundry 3.', Factories::class));

            return;
        }

        $this->_bootFoundry();
        $this->_loadDataProvidedProxies();
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

    /**
     * If a persistent object has been created in a data provider, we need to initialize the proxy object,
     * which will trigger the object to be persisted.
     *
     * Otherwise, such test would not pass:
     * ```php
     * #[DataProvider('provide')]
     * public function testSomething(MyEntity $entity): void
     * {
     *     MyEntityFactory::assert()->count(1);
     * }
     *
     * public static function provide(): iterable
     * {
     *     yield [MyEntityFactory::createOne()];
     * }
     * ```
     *
     * Sadly, this cannot be done in a subscriber, since PHPUnit does not give access to the actual tests instances.
     *
     * @internal
     */
    private function _loadDataProvidedProxies(): void
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore function.impossibleType, function.alreadyNarrowedType
            return;
        }

        $providedData = \method_exists($this, 'getProvidedData') // @phpstan-ignore function.impossibleType
            ? $this->getProvidedData() // @phpstan-ignore method.notFound
            : $this->providedData();

        initialize_proxy_object($providedData);
    }
}
