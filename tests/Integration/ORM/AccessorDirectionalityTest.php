<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\AccessorDirectionality\CategoryEntity;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\AccessorDirectionality\ItemEntity;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * The accessor of the side being built is used; the other side is kept
 * consistent silently, without triggering its accessor's side effects.
 *
 * @see https://github.com/zenstruck/foundry/pull/1104
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class AccessorDirectionalityTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /** @test */
    #[Test]
    public function adder_is_used_when_creating_from_the_collection_side(): void
    {
        $categoryFactory = persistent_factory(CategoryEntity::class);
        $itemFactory = persistent_factory(ItemEntity::class);

        $category = $categoryFactory->create(['items' => $itemFactory->many(2)]);

        self::assertSame(2, $category->adderCalls);
        self::assertCount(2, $category->getItems());

        $item = $category->getItems()->first();
        self::assertNotFalse($item);
        self::assertSame($category, $item->getCategory());
        $itemFactory::assert()->count(2);
    }

    /** @test */
    #[Test]
    public function adder_side_effects_are_not_triggered_when_creating_from_the_owning_side(): void
    {
        $categoryFactory = persistent_factory(CategoryEntity::class);
        $itemFactory = persistent_factory(ItemEntity::class);

        $item = $itemFactory->create(['category' => $categoryFactory]);

        $category = $item->getCategory();
        self::assertNotNull($category);
        self::assertSame(0, $category->adderCalls);
        self::assertGreaterThanOrEqual(1, $item->setterCalls);
        self::assertCount(1, $category->getItems());
        $itemFactory::assert()->count(1);
    }
}
