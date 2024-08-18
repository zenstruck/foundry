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

use function Zenstruck\Foundry\InMemory\should_enable_in_memory;
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
    #[Before]
    public function _beforeHook(): void
    {
        $this->_bootFoundry();
        $this->_enableInMemory();
        $this->_loadDataProvidedProxies();
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

    /**
     * @internal
     */
    private function _bootFoundry(): void
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
     */
    private function _enableInMemory(): void
    {
        $method = \method_exists($this, 'name') ? $this->name() : $this->getName(false); // @phpstan-ignore method.notFound

        if (!\is_subclass_of(static::class, KernelTestCase::class) || !should_enable_in_memory(static::class, $method)) { // @phpstan-ignore-line
            return;
        }

        Configuration::instance()->enableInMemory();
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
        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore-line
            return;
        }

        $providedData = \method_exists($this, 'getProvidedData') ? $this->getProvidedData() : $this->providedData(); // @phpstan-ignore method.notFound

        initialize_proxy_object($providedData);
    }
}
