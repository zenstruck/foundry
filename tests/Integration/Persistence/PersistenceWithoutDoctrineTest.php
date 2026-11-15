<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Tests\Fixture\CustomPersistence\Widget;
use Zenstruck\Foundry\Tests\Fixture\CustomPersistence\WidgetPersistenceStrategy;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;

use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\repository;

/**
 * Runs in every permutation, including the one without any Doctrine bundle.
 */
final class PersistenceWithoutDoctrineTest extends KernelTestCase
{
    #[Test]
    public function a_custom_strategy_persists_and_reads(): void
    {
        $widget = persistent_factory(Widget::class, ['id' => 1])->create();

        $strategy = self::getContainer()->get(WidgetPersistenceStrategy::class);
        \assert($strategy instanceof WidgetPersistenceStrategy);

        self::assertSame([$widget], $strategy->persisted);
        self::assertSame($widget, repository(Widget::class)->find(1));
    }

    #[Test]
    #[AsInMemoryTest]
    public function the_in_memory_mode_stores_and_reads(): void
    {
        $address = AddressFactory::createOne();

        self::assertSame([$address], AddressFactory::repository()->findAll());
    }
}
