<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Brand;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Image;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Product;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\ProductCategory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Review;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Tag;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Variant;

use function Zenstruck\Foundry\anonymous;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryDoctrineCascadeTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    protected function setUp(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
    }

    /**
     * @test
     */
    public function many_to_one_relationship(): void
    {
        $product = anonymous(Product::class, [
            'name' => 'foo',
            'brand' => anonymous(Brand::class, ['name' => 'bar']),
        ])->instantiateWith(function(array $attributes, string $class): object {
            $this->assertNull($attributes['brand']->getId());

            return (new Instantiator())($attributes, $class);
        })->create();

        $this->assertNotNull($product->getBrand()->getId());
        $this->assertSame('bar', $product->getBrand()->getName());
    }

    /**
     * @test
     */
    public function one_to_many_relationship(): void
    {
        $brand = anonymous(Brand::class, [
            'name' => 'brand',
            'products' => anonymous(Product::class, ['name' => 'product'])->many(2),
        ])->instantiateWith(function(array $attributes, string $class): object {
            $this->assertNull($attributes['products'][0]->getId());

            return (new Instantiator())($attributes, $class);
        })->create();

        $this->assertCount(2, $brand->getProducts());
        $this->assertNotNull($brand->getProducts()->first()->getId());
        $this->assertSame('product', $brand->getProducts()->first()->getName());
    }

    /**
     * @test
     */
    public function many_to_many_relationship(): void
    {
        $product = anonymous(Product::class, [
            'name' => 'foo',
            'tags' => [anonymous(Tag::class, ['name' => 'bar'])],
        ])->instantiateWith(function(array $attributes, string $class): object {
            $this->assertNull($attributes['tags'][0]->getId());

            return (new Instantiator())($attributes, $class);
        })->create();

        $this->assertCount(1, $product->getTags());
        $this->assertNotNull($product->getTags()->first()->getId());
        $this->assertSame('bar', $product->getTags()->first()->getName());
    }

    /**
     * @test
     */
    public function many_to_many_reverse_relationship(): void
    {
        $product = anonymous(Product::class, [
            'name' => 'foo',
            'categories' => [anonymous(ProductCategory::class, ['name' => 'bar'])],
        ])->instantiateWith(function(array $attributes, string $class): object {
            $this->assertNull($attributes['categories'][0]->getId());

            return (new Instantiator())($attributes, $class);
        })->create();

        $this->assertCount(1, $product->getCategories());
        $this->assertNotNull($product->getCategories()->first()->getId());
        $this->assertSame('bar', $product->getCategories()->first()->getName());
    }

    /**
     * @test
     */
    public function one_to_one_relationship(): void
    {
        $product = anonymous(Product::class, [
            'name' => 'foo',
            'review' => anonymous(Review::class, ['rank' => 5]),
        ])->instantiateWith(function(array $attributes, string $class): object {
            $this->assertNull($attributes['review']->getId());

            return (new Instantiator())($attributes, $class);
        })->create();

        $this->assertNotNull($product->getReview()->getId());
        $this->assertSame(5, $product->getReview()->getRank());
    }

    /**
     * @test
     */
    public function one_to_one_reverse_relationship(): void
    {
        $product = anonymous(Product::class, [
            'name' => 'foo',
            'review' => anonymous(Review::class, ['rank' => 4]),
        ])->instantiateWith(function(array $attributes, string $class): object {
            $this->assertNull($attributes['review']->getId());

            return (new Instantiator())($attributes, $class);
        })->create();

        $this->assertNotNull($product->getReview()->getId());
        $this->assertSame(4, $product->getReview()->getRank());
    }

    /**
     * @test
     */
    public function nested_relationship_without_cascade(): void
    {
        $product = anonymous(Product::class, [
            'name' => 'foo',
            'variants' => [
                anonymous(Variant::class, [
                    'name' => 'bar',
                    // asserts a "sub" relationship without cascade persist is persisted
                    'image' => anonymous(Image::class, ['path' => '/some/path']),
                ]),
            ],
        ])->create();

        $this->assertSame('/some/path', $product->getVariants()->first()->getImage()->getPath());
    }

    /**
     * @test
     */
    public function nested_collections_with_cascade(): void
    {
        $brand = anonymous(Brand::class, [
            'name' => 'brand',
            'products' => anonymous(Product::class, [
                'name' => 'product',
                'variants' => anonymous(Variant::class, ['name' => 'variant'])->many(3),
            ])->many(2),
        ])->create();

        $this->assertCount(2, $brand->getProducts());
        foreach ($brand->getProducts() as $product) {
            $this->assertCount(3, $product->getVariants());
        }
    }
}
