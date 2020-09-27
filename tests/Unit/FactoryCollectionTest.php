<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\UnitTestCase;
use function Zenstruck\Foundry\factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryCollectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function can_create_with_static_size(): void
    {
        $collection = new FactoryCollection(factory(Category::class)->withoutPersisting(), 2);

        $this->assertCount(2, $collection->create());
        $this->assertCount(2, $collection->create());
        $this->assertCount(2, $collection->create());
        $this->assertCount(2, $collection->create());
        $this->assertCount(2, $collection->create());
    }

    /**
     * @test
     */
    public function can_create_with_random_range(): void
    {
        $collection = new FactoryCollection(factory(Category::class)->withoutPersisting(), 0, 3);
        $counts = [];

        while (4 !== \count(\array_unique($counts))) {
            $counts[] = \count($collection->create());
        }

        $this->assertCount(4, \array_unique($counts));
        $this->assertContains(0, $counts);
        $this->assertContains(1, $counts);
        $this->assertContains(2, $counts);
        $this->assertContains(3, $counts);
        $this->assertNotContains(4, $counts);
        $this->assertNotContains(5, $counts);
    }

    /**
     * @test
     */
    public function min_must_be_less_than_or_equal_to_max(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectDeprecationMessage('Min must be less than max.');

        new FactoryCollection(factory(Category::class)->withoutPersisting(), 4, 3);
    }
}
