<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithInvalidInitialize;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithNullInitialize;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithValidInitialize;
use Zenstruck\Foundry\Tests\Fixtures\Factories\TagFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\UserFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ModelFactoryTest extends KernelTestCase
{
    use ResetDatabase, Factories;

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
    public function can_find_or_create_using_prefixed_fields(): void
    {
        CategoryFactory::findOrCreate(['force:name' => 'php', 'optional:version' => 7.4]);
        CategoryFactory::repository()->assertCount(1);
        CategoryFactory::findOrCreate(['force:name' => 'php', 'optional:version' => 7.4]);
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
    public function one_to_many_with_nested_collection_relationship(): void
    {
        $post = PostFactory::new()->create([
            'comments' => CommentFactory::new()->many(4),
        ]);

        $this->assertCount(4, $post->getComments());
        UserFactory::repository()->assertCount(4);
        CommentFactory::repository()->assertCount(4);
        PostFactory::repository()->assertCount(1);
    }

    /**
     * @test
     */
    public function create_multiple_one_to_many_with_nested_collection_relationship(): void
    {
        $user = UserFactory::new()->create();
        $posts = PostFactory::new()->createMany(2, [
            'comments' => CommentFactory::new(['user' => $user])->many(4),
        ]);

        $this->assertCount(4, $posts[0]->getComments());
        $this->assertCount(4, $posts[1]->getComments());
        UserFactory::repository()->assertCount(1);
        CommentFactory::repository()->assertCount(8);
        PostFactory::repository()->assertCount(2);
    }

    /**
     * @test
     */
    public function many_to_many_with_nested_collection_relationship(): void
    {
        $post = PostFactory::new()->create([
            'tags' => TagFactory::new()->many(3),
        ]);

        $this->assertCount(3, $post->getTags());
        TagFactory::repository()->assertCount(5); // 3 created by this test and 2 in global state
        PostFactory::repository()->assertCount(1);
    }

    /**
     * @test
     */
    public function inverse_many_to_many_with_nested_collection_relationship(): void
    {
        $tag = TagFactory::new()->create([
            'posts' => PostFactory::new()->many(3),
        ]);

        $this->assertCount(3, $tag->getPosts());
        TagFactory::repository()->assertCount(3); // 1 created by this test and 2 in global state
        PostFactory::repository()->assertCount(3);
    }

    /**
     * @test
     */
    public function create_multiple_many_to_many_with_nested_collection_relationship(): void
    {
        $posts = PostFactory::new()->createMany(2, [
            'tags' => TagFactory::new()->many(3),
        ]);

        $this->assertCount(3, $posts[0]->getTags());
        $this->assertCount(3, $posts[1]->getTags());
        TagFactory::repository()->assertCount(8); // 6 created by this test and 2 in global state
        PostFactory::repository()->assertCount(2);
    }

    /**
     * @test
     */
    public function unpersisted_one_to_many_with_nested_collection_relationship(): void
    {
        $post = PostFactory::new()->withoutPersisting()->create([
            'comments' => CommentFactory::new()->many(4),
        ]);

        $this->assertCount(4, $post->getComments());
        UserFactory::repository()->assertEmpty();
        CommentFactory::repository()->assertEmpty();
        PostFactory::repository()->assertEmpty();
    }

    /**
     * @test
     */
    public function unpersisted_many_to_many_with_nested_collection_relationship(): void
    {
        $post = PostFactory::new()->withoutPersisting()->create([
            'tags' => TagFactory::new()->many(3),
        ]);

        $this->assertCount(3, $post->getTags());
        TagFactory::repository()->assertCount(2); // 2 created in global state
        PostFactory::repository()->assertEmpty();
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function can_use_model_factories_in_a_data_provider(PostFactory $factory, bool $published): void
    {
        $post = $factory->create();

        $post->assertPersisted();
        $this->assertSame($published, $post->isPublished());
    }

    public static function dataProvider(): array
    {
        return [
            [PostFactory::new(), false],
            [PostFactory::new()->published(), true],
        ];
    }
}
