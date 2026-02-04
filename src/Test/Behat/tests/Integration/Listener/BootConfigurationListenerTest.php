<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Integration\Behat\Listener;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Behat\Listener\BootConfigurationListener;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

final class BootConfigurationListenerTest extends KernelTestCase
{
    #[Test]
    public function it_boots_foundry_when_not_already_booted(): void
    {
        Configuration::shutdown();
        self::assertFalse(Configuration::isBooted());

        $listener = $this->createListener();
        $listener->bootFoundry();

        self::assertTrue(Configuration::isBooted());
    }

    #[Test]
    public function it_shuts_down_foundry(): void
    {
        self::assertTrue(Configuration::isBooted());

        $listener = $this->createListener();
        $listener->shutdownFoundry();

        self::assertFalse(Configuration::isBooted());
    }

    #[Test]
    public function it_shuts_down_foundry_after_feature_and_resets_registry(): void
    {
        $testObj = GenericEntityFactory::createOne();
        $registry = $this->objectRegistry();
        $registry->store($testObj, 'test-object');

        self::assertTrue($registry->isStored($testObj));
        self::assertTrue(Configuration::isBooted());

        $listener = $this->createListener();
        $listener->shutdownFoundryAfterFeature();

        self::assertFalse($registry->isStored($testObj));
        self::assertFalse(Configuration::isBooted());
    }

    private function objectRegistry(): ObjectRegistry
    {
        return self::getContainer()->get('.zenstruck_foundry.behat.object_registry'); // @phpstan-ignore return.type
    }

    private function createListener(): BootConfigurationListener
    {
        return new BootConfigurationListener(self::$kernel ?? self::bootKernel());
    }
}
