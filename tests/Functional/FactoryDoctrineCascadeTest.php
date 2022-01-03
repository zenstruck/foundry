<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Brand;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Image;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Product;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Review;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Tag;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Variant;
use function Zenstruck\Foundry\factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryDoctrineCascadeTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    protected function setUp(): void
    {
        if (false === \getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
    }

    /**
     * @test
     */
    public function many_to_one_relationship(): void
    {
        $product = factory(Product::class, [
            'name' => 'foo',
            'brand' => factory(Brand::class, ['name' => 'bar']),
        ])->instantiateWith(function(array $attibutes, string $class): object {
            $this->assertNull($attibutes['brand']->getId());

            return (new Instantiator())($attibutes, $class);
        })->create();

        $this->assertNotNull($product->getBrand()->getId());
        $this->assertSame('bar', $product->getBrand()->getName());
    }

    /**
     * @test
     */
    public function one_to_many_relationship(): void
    {
        $product = factory(Product::class, [
            'name' => 'foo',
            'variants' => [factory(Variant::class, ['name' => 'bar'])],
        ])->instantiateWith(function(array $attibutes, string $class): object {
            $this->assertNull($attibutes['variants'][0]->getId());

            return (new Instantiator())($attibutes, $class);
        })->create();

        $this->assertCount(1, $product->getVariants());
        $this->assertNotNull($product->getVariants()->first()->getId());
        $this->assertSame('bar', $product->getVariants()->first()->getName());
    }

    /**
     * @test
     */
    public function many_to_many_relationship(): void
    {
        $product = factory(Product::class, [
            'name' => 'foo',
            'tags' => [factory(Tag::class, ['name' => 'bar'])],
        ])->instantiateWith(function(array $attibutes, string $class): object {
            $this->assertNull($attibutes['tags'][0]->getId());

            return (new Instantiator())($attibutes, $class);
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
        $product = factory(Product::class, [
            'name' => 'foo',
            'categories' => [factory(Category::class, ['name' => 'bar'])],
        ])->instantiateWith(function(array $attibutes, string $class): object {
            $this->assertNull($attibutes['categories'][0]->getId());

            return (new Instantiator())($attibutes, $class);
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
        $variant = factory(Variant::class, [
            'name' => 'foo',
            'image' => factory(Image::class, ['path' => '/path/to/file.extension']),
        ])->instantiateWith(function(array $attibutes, string $class): object {
            $this->assertNull($attibutes['image']->getId());

            return (new Instantiator())($attibutes, $class);
        })->create();

        $this->assertNotNull($variant->getImage()->getId());
        $this->assertSame('/path/to/file.extension', $variant->getImage()->getPath());
    }

    /**
     * @test
     */
    public function one_to_one_reverse_relationship(): void
    {
        $product = factory(Product::class, [
            'name' => 'foo',
            'review' => factory(Review::class, ['rank' => 4]),
        ])->instantiateWith(function(array $attibutes, string $class): object {
            $this->assertNull($attibutes['review']->getId());

            return (new Instantiator())($attibutes, $class);
        })->create();

        $this->assertNotNull($product->getReview()->getId());
        $this->assertSame(4, $product->getReview()->getRank());
    }
}
