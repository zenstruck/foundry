<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;

use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryCollectionTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    public function can_create_with_static_size(): void
    {
        $collection = FactoryCollection::many(persistent_factory(Category::class), 2);

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
        $collection = FactoryCollection::range(persistent_factory(Category::class), 0, 3);
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
        $this->expectExceptionMessage('Min must be less than max.');

        FactoryCollection::range(persistent_factory(Category::class), 4, 3);
    }

    /**
     * @test
     */
    public function can_create_with_sequence(): void
    {
        $collection = FactoryCollection::sequence(persistent_factory(Category::class), [['name' => 'foo'], ['name' => 'bar']]);

        $categories = $collection->create();
        $this->assertCount(2, $categories);
        $this->assertSame('foo', $categories[0]->getName());
        $this->assertSame('bar', $categories[1]->getName());
    }
}
