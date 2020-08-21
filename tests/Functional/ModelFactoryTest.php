<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithInvalidInitialize;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithNullInitialize;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithValidInitialize;
use Zenstruck\Foundry\Tests\Fixtures\Factories\UserFactory;
use Zenstruck\Foundry\Tests\FunctionalTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ModelFactoryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function can_find_or_create(): void
    {
        CategoryFactory::repository()->assertCount(0);
        CategoryFactory::findOrCreate(['name' => 'php']);
        CategoryFactory::repository()->assertCount(1);
        CategoryFactory::findOrCreate(['name' => 'php']);
        CategoryFactory::repository()->assertCount(1);
    }

    /**
     * @test
     */
    public function can_override_initialize(): void
    {
        $this->assertFalse(PostFactory::new()->create()->isPublished());
        $this->assertTrue(PostFactoryWithValidInitialize::new()->create()->isPublished());
    }

    /**
     * @test
     */
    public function initialize_must_return_an_instance_of_the_current_factory(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(\sprintf('"%1$s::initialize()" must return an instance of "%1$s".', PostFactoryWithInvalidInitialize::class));

        PostFactoryWithInvalidInitialize::new();
    }

    /**
     * @test
     */
    public function initialize_must_return_a_value(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(\sprintf('"%1$s::initialize()" must return an instance of "%1$s".', PostFactoryWithNullInitialize::class));

        PostFactoryWithNullInitialize::new();
    }

    /**
     * @test
     */
    public function can_find_random_object(): void
    {
        CategoryFactory::new()->createMany(5);

        $ids = [];

        while (5 !== \count(\array_unique($ids))) {
            $ids[] = CategoryFactory::random()->getId();
        }

        $this->assertCount(5, \array_unique($ids));
    }

    /**
     * @test
     */
    public function can_find_random_set_of_objects(): void
    {
        CategoryFactory::new()->createMany(5);

        $objects = CategoryFactory::randomSet(3);

        $this->assertCount(3, $objects);
        $this->assertCount(3, \array_unique(\array_map(static function($category) { return $category->getId(); }, $objects)));
    }

    /**
     * @test
     */
    public function can_find_random_range_of_objects(): void
    {
        CategoryFactory::new()->createMany(5);

        $counts = [];

        while (4 !== \count(\array_unique($counts))) {
            $counts[] = \count(CategoryFactory::randomRange(0, 3));
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
    public function one_to_many_with_nested_relationship(): void
    {
        $post = PostFactory::new()->create([
            'comments' => [
                CommentFactory::new(),
                CommentFactory::new(),
                CommentFactory::new(),
                CommentFactory::new(),
            ],
        ]);

        $this->assertCount(4, $post->getComments());
        UserFactory::repository()->assertCount(4);
        PostFactory::repository()->assertCount(1); // fails (count=5, 1 primary, 1 for each comment)
    }
}
