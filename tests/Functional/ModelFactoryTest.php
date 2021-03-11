<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
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
    use Factories, ResetDatabase;

    /**
     * @test
     */
    public function can_find_or_create(): void
    {
        CategoryFactory::assert()->count(0);
        CategoryFactory::findOrCreate(['name' => 'php']);
        CategoryFactory::assert()->count(1);
        CategoryFactory::findOrCreate(['name' => 'php']);
        CategoryFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_override_initialize(): void
    {
        $this->assertFalse(PostFactory::createOne()->isPublished());
        $this->assertTrue(PostFactoryWithValidInitialize::createOne()->isPublished());
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
        CategoryFactory::createMany(5);

        $ids = [];

        while (5 !== \count(\array_unique($ids))) {
            $ids[] = CategoryFactory::random()->getId();
        }

        $this->assertCount(5, \array_unique($ids));
    }

    /**
     * @test
     */
    public function can_create_random_object_if_none_exists(): void
    {
        CategoryFactory::assert()->count(0);
        $this->assertInstanceOf(Category::class, CategoryFactory::randomOrCreate()->object());
        CategoryFactory::assert()->count(1);
        $this->assertInstanceOf(Category::class, CategoryFactory::randomOrCreate()->object());
        CategoryFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_get_or_create_random_object_with_attributes(): void
    {
        CategoryFactory::createMany(5, ['name' => 'name1']);

        CategoryFactory::assert()->count(5);
        $this->assertSame('name2', CategoryFactory::randomOrCreate(['name' => 'name2'])->getName());
        CategoryFactory::assert()->count(6);
        $this->assertSame('name2', CategoryFactory::randomOrCreate(['name' => 'name2'])->getName());
        CategoryFactory::assert()->count(6);
    }

    /**
     * @test
     */
    public function can_find_random_set_of_objects(): void
    {
        CategoryFactory::createMany(5);

        $objects = CategoryFactory::randomSet(3);

        $this->assertCount(3, $objects);
        $this->assertCount(3, \array_unique(\array_map(static function($category) { return $category->getId(); }, $objects)));
    }

    /**
     * @test
     */
    public function can_find_random_set_of_objects_with_attributes(): void
    {
        CategoryFactory::createMany(20, ['name' => 'name1']);
        CategoryFactory::createMany(5, ['name' => 'name2']);

        $objects = CategoryFactory::randomSet(2, ['name' => 'name2']);

        $this->assertCount(2, $objects);
        $this->assertSame('name2', $objects[0]->getName());
        $this->assertSame('name2', $objects[1]->getName());
    }

    /**
     * @test
     */
    public function can_find_random_range_of_objects(): void
    {
        CategoryFactory::createMany(5);

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
    public function can_find_random_range_of_objects_with_attributes(): void
    {
        CategoryFactory::createMany(20, ['name' => 'name1']);
        CategoryFactory::createMany(5, ['name' => 'name2']);

        $objects = CategoryFactory::randomRange(2, 4, ['name' => 'name2']);

        $this->assertGreaterThanOrEqual(2, \count($objects));
        $this->assertLessThanOrEqual(4, \count($objects));

        foreach ($objects as $object) {
            $this->assertSame('name2', $object->getName());
        }
    }

    /**
     * @test
     */
    public function one_to_many_with_nested_collection_relationship(): void
    {
        $post = PostFactory::createOne([
            'comments' => CommentFactory::new()->many(4),
        ]);

        $this->assertCount(4, $post->getComments());
        UserFactory::assert()->count(4);
        CommentFactory::assert()->count(4);
        PostFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function create_multiple_one_to_many_with_nested_collection_relationship(): void
    {
        $user = UserFactory::createOne();
        $posts = PostFactory::createMany(2, [
            'comments' => CommentFactory::new(['user' => $user])->many(4),
        ]);

        $this->assertCount(4, $posts[0]->getComments());
        $this->assertCount(4, $posts[1]->getComments());
        UserFactory::assert()->count(1);
        CommentFactory::assert()->count(8);
        PostFactory::assert()->count(2);
    }

    /**
     * @test
     */
    public function many_to_many_with_nested_collection_relationship(): void
    {
        $post = PostFactory::createOne([
            'tags' => TagFactory::new()->many(3),
        ]);

        $this->assertCount(3, $post->getTags());
        TagFactory::assert()->count(5); // 3 created by this test and 2 in global state
        PostFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function inverse_many_to_many_with_nested_collection_relationship(): void
    {
        $tag = TagFactory::createOne([
            'posts' => PostFactory::new()->many(3),
        ]);

        $this->assertCount(3, $tag->getPosts());
        TagFactory::assert()->count(3); // 1 created by this test and 2 in global state
        PostFactory::assert()->count(3);
    }

    /**
     * @test
     */
    public function create_multiple_many_to_many_with_nested_collection_relationship(): void
    {
        $posts = PostFactory::createMany(2, [
            'tags' => TagFactory::new()->many(3),
        ]);

        $this->assertCount(3, $posts[0]->getTags());
        $this->assertCount(3, $posts[1]->getTags());
        TagFactory::assert()->count(8); // 6 created by this test and 2 in global state
        PostFactory::assert()->count(2);
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
        UserFactory::assert()->empty();
        CommentFactory::assert()->empty();
        PostFactory::assert()->empty();
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
        TagFactory::assert()->count(2); // 2 created in global state
        PostFactory::assert()->empty();
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

    /**
     * @test
     */
    public function many_to_one_unmanaged_entity(): void
    {
        $category = CategoryFactory::createOne(['name' => 'My Category']);

        self::$container->get(EntityManagerInterface::class)->clear();

        $post = PostFactory::createOne(['category' => $category]);

        $this->assertSame('My Category', $post->getCategory()->getName());
    }

    /**
     * @test
     */
    public function many_to_one_unmanaged_raw_entity(): void
    {
        $category = CategoryFactory::createOne(['name' => 'My Category'])->object();

        self::$container->get(EntityManagerInterface::class)->clear();

        $post = PostFactory::createOne(['category' => $category]);

        $this->assertSame('My Category', $post->getCategory()->getName());
    }

    /**
     * @test
     */
    public function many_to_one_managed_entity_with_pending_changes(): void
    {
        $category = CategoryFactory::createOne(['name' => 'My Category']);
        $category->setName('New Category Name');

        $post = PostFactory::createOne(['category' => $category]);

        $category->save();

        $this->assertSame('New Category Name', $post->getCategory()->getName());
    }

    /**
     * @test
     */
    public function many_to_one_managed_raw_entity_with_pending_changes(): void
    {
        $category = CategoryFactory::createOne(['name' => 'My Category'])->object();
        $category->setName('New Category Name');

        $post = PostFactory::createOne(['category' => $category]);

        CategoryFactory::repository()->flush();

        $this->assertSame('New Category Name', $post->getCategory()->getName());
    }

    /**
     * @test
     */
    public function first_and_last_return_the_correct_object(): void
    {
        $categoryA = CategoryFactory::createOne(['name' => '3']);
        $categoryB = CategoryFactory::createOne(['name' => '2']);
        $categoryC = CategoryFactory::createOne(['name' => '1']);

        $this->assertSame($categoryA->getId(), CategoryFactory::first()->getId());
        $this->assertSame($categoryC->getId(), CategoryFactory::first('name')->getId());
        $this->assertSame($categoryC->getId(), CategoryFactory::last()->getId());
        $this->assertSame($categoryA->getId(), CategoryFactory::last('name')->getId());
    }

    /**
     * @test
     */
    public function first_throws_exception_if_no_entities_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        CategoryFactory::first();
    }

    /**
     * @test
     */
    public function last_throws_exception_if_no_entities_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        CategoryFactory::last();
    }

    /**
     * @test
     */
    public function can_count_and_truncate_model_factory(): void
    {
        $this->assertSame(0, CategoryFactory::count());

        CategoryFactory::createMany(4);

        $this->assertSame(4, CategoryFactory::count());

        CategoryFactory::truncate();

        $this->assertSame(0, CategoryFactory::count());
    }

    /**
     * @test
     */
    public function can_get_all_entities(): void
    {
        $this->assertSame([], CategoryFactory::all());

        CategoryFactory::createMany(4);

        $categories = CategoryFactory::all();

        $this->assertCount(4, $categories);
        $this->assertInstanceOf(Category::class, $categories[0]->object());
        $this->assertInstanceOf(Category::class, $categories[1]->object());
        $this->assertInstanceOf(Category::class, $categories[2]->object());
        $this->assertInstanceOf(Category::class, $categories[3]->object());
    }

    /**
     * @test
     */
    public function can_find_entity(): void
    {
        CategoryFactory::createOne(['name' => 'first']);
        CategoryFactory::createOne(['name' => 'second']);
        $category = CategoryFactory::createOne(['name' => 'third']);

        $this->assertSame('second', CategoryFactory::find(['name' => 'second'])->getName());
        $this->assertSame('third', CategoryFactory::find(['id' => $category->getId()])->getName());
        $this->assertSame('third', CategoryFactory::find($category->getId())->getName());
        $this->assertSame('third', CategoryFactory::find($category->object())->getName());
        $this->assertSame('third', CategoryFactory::find($category)->getName());
    }

    /**
     * @test
     */
    public function find_throws_exception_if_no_entities_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        CategoryFactory::find(99);
    }

    /**
     * @test
     */
    public function can_find_by(): void
    {
        $this->assertSame([], CategoryFactory::findBy(['name' => 'name2']));

        CategoryFactory::createOne(['name' => 'name1']);
        CategoryFactory::createOne(['name' => 'name2']);
        CategoryFactory::createOne(['name' => 'name2']);

        $categories = CategoryFactory::findBy(['name' => 'name2']);

        $this->assertCount(2, $categories);
        $this->assertSame('name2', $categories[0]->getName());
        $this->assertSame('name2', $categories[1]->getName());
    }
}
