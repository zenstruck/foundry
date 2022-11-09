<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Factories\AddressFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ContactFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithInvalidInitialize;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithNullInitialize;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactoryWithValidInitialize;
use Zenstruck\Foundry\Tests\Fixtures\Factories\SpecificPostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\TagFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\UserFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ORMModelFactoryTest extends ModelFactoryTest
{
    protected function setUp(): void
    {
        if (false === \getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
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
    public function one_to_many_with_two_relationships_same_entity(): void
    {
        $category = CategoryFactory::createOne([
            'posts' => PostFactory::new()->many(4),
            'secondaryPosts' => PostFactory::new()->many(4),
        ]);

        $this->assertCount(4, $category->getPosts());
        $this->assertCount(4, $category->getSecondaryPosts());
        PostFactory::assert()->count(8);
        CategoryFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function inverse_one_to_many_relationship_without_cascade(): void
    {
        UserFactory::createOne([
            'comments' => [CommentFactory::new()],
        ]);

        UserFactory::assert()->count(1);
        CommentFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function many_to_one_with_two_relationships_same_entity(): void
    {
        $post = PostFactory::createOne([
            'category' => CategoryFactory::new(['name' => 'foo']),
            'secondaryCategory' => CategoryFactory::new(['name' => 'bar']),
        ]);

        $this->assertNotNull($category = $post->getCategory());
        $this->assertNotNull($secondaryCategory = $post->getSecondaryCategory());
        $this->assertSame('foo', $category->getName());
        $this->assertSame('bar', $secondaryCategory->getName());
        PostFactory::assert()->count(1);
        CategoryFactory::assert()->count(2);
    }

    /**
     * @test
     */
    public function one_to_one_relationship_polymorphic(): void
    {
        SpecificPostFactory::createOne([
            'mostRelevantRelatedPost' => SpecificPostFactory::new(),
        ]);

        SpecificPostFactory::assert()->count(2);
        PostFactory::assert()->count(2); // 2 specific
    }

    /**
     * @test
     */
    public function inverse_one_to_one_relationship_polymorphic(): void
    {
        SpecificPostFactory::createOne([
            'mostRelevantRelatedToPost' => SpecificPostFactory::new(),
        ]);

        SpecificPostFactory::assert()->count(2);
        PostFactory::assert()->count(2); // 2 specific
    }

    /**
     * @test
     */
    public function one_to_many_polymorphic_with_nested_collection_relationship(): void
    {
        $post = SpecificPostFactory::createOne([
            'comments' => CommentFactory::new()->many(4),
        ]);

        $this->assertCount(4, $post->getComments());
        CommentFactory::assert()->count(4);
        SpecificPostFactory::assert()->count(1);
        PostFactory::assert()->count(1); // 1 specific
    }

    /**
     * @test
     */
    public function one_to_many_with_nested_collection_relationship_polymorphic(): void
    {
        $category = CategoryFactory::createOne([
            'posts' => SpecificPostFactory::new()->many(3),
        ]);

        $this->assertCount(3, $category->getPosts());
        CategoryFactory::assert()->count(1);
        SpecificPostFactory::assert()->count(3);
        PostFactory::assert()->count(3); // 3 specific
    }

    /**
     * @test
     */
    public function one_to_one_with_two_relationships_same_entity(): void
    {
        $post = PostFactory::createOne([
            'mostRelevantRelatedPost' => PostFactory::new(['title' => 'foo']),
            'lessRelevantRelatedPost' => PostFactory::new(['title' => 'bar']),
        ]);

        $this->assertNotNull($mostRelevantRelatedPost = $post->getMostRelevantRelatedPost());
        $this->assertNotNull($lessRelevantRelatedPost = $post->getLessRelevantRelatedPost());
        $this->assertSame('foo', $mostRelevantRelatedPost->getTitle());
        $this->assertSame('bar', $lessRelevantRelatedPost->getTitle());
        PostFactory::assert()->count(3);
    }

    /**
     * @test
     */
    public function one_to_many_with_nested_collection_relationship_polymorphic_mixed(): void
    {
        $category = CategoryFactory::createOne([
            'posts' => [PostFactory::new(), SpecificPostFactory::new()],
        ]);

        $this->assertCount(2, $category->getPosts());
        CategoryFactory::assert()->count(1);
        SpecificPostFactory::assert()->count(1);
        PostFactory::assert()->count(2); // 2 posts with the specific ones
    }

    /**
     * @test
     */
    public function inverse_one_to_one_with_two_relationships_same_entity(): void
    {
        $post = PostFactory::createOne([
            'mostRelevantRelatedToPost' => PostFactory::new(['title' => 'foo']),
            'lessRelevantRelatedToPost' => PostFactory::new(['title' => 'bar']),
        ]);

        $this->assertNotNull($mostRelevantRelatedToPost = $post->getMostRelevantRelatedToPost());
        $this->assertNotNull($lessRelevantRelatedToPost = $post->getLessRelevantRelatedToPost());
        $this->assertSame('foo', $mostRelevantRelatedToPost->getTitle());
        $this->assertSame('bar', $lessRelevantRelatedToPost->getTitle());
        PostFactory::assert()->count(3);
    }

    /**
     * @test
     */
    public function many_to_one_relationship_polymorphic(): void
    {
        $user = UserFactory::createOne();
        CommentFactory::createOne([
            'user' => $user,
            'post' => SpecificPostFactory::new(),
        ]);

        CommentFactory::assert()->count(1);
        SpecificPostFactory::assert()->count(1);
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
        TagFactory::assert()->count(
            \getenv('USE_FOUNDRY_BUNDLE')
                ? 5  // 3 created by this test and 2 in global state
                : 3
        );
        PostFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function many_to_many_with_two_relationships_same_entity(): void
    {
        $post = PostFactory::createOne([
            'tags' => TagFactory::new()->many(3),
            'secondaryTags' => TagFactory::new()->many(3),
        ]);

        $this->assertCount(3, $post->getTags());
        $this->assertCount(3, $post->getSecondaryTags());
        TagFactory::assert()->count(
            \getenv('USE_FOUNDRY_BUNDLE')
                ? 8  // 6 created by this test and 2 in global state
                : 6
        );
        PostFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function many_to_many_with_nested_collection_relationship_polymorphic(): void
    {
        $post = SpecificPostFactory::createOne([
            'relatedPosts' => SpecificPostFactory::new()->many(3),
        ]);

        $this->assertCount(3, $post->getRelatedPosts());
        SpecificPostFactory::assert()->count(4);
        PostFactory::assert()->count(4); // 4 posts with the specific ones
    }

    /**
     * @test
     */
    public function many_to_many_with_nested_collection_relationship_polymorphic_mixed(): void
    {
        $post = SpecificPostFactory::createOne([
            'relatedPosts' => [PostFactory::new(), SpecificPostFactory::new()],
        ]);

        $this->assertCount(2, $post->getRelatedPosts());
        SpecificPostFactory::assert()->count(2);
        PostFactory::assert()->count(3); // 3 posts with the specific ones
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
        TagFactory::assert()->count(
            \getenv('USE_FOUNDRY_BUNDLE')
                ? 3 // 1 created by this test and 2 in global state
                : 1
        );
        PostFactory::assert()->count(3);
    }

    /**
     * @test
     */
    public function inverse_many_to_many_with_two_relationships_same_entity(): void
    {
        $tag = TagFactory::createOne([
            'posts' => PostFactory::new()->many(3),
            'secondaryPosts' => PostFactory::new()->many(3),
        ]);

        $this->assertCount(3, $tag->getPosts());
        $this->assertCount(3, $tag->getSecondaryPosts());
        TagFactory::assert()->count(
            \getenv('USE_FOUNDRY_BUNDLE')
                ? 3 // 1 created by this test and 2 in global state
                : 1
        );
        PostFactory::assert()->count(6);
    }

    /**
     * @test
     */
    public function inverse_many_to_many_with_nested_collection_relationship_polymorphic(): void
    {
        $post = SpecificPostFactory::createOne([
            'relatedToPosts' => SpecificPostFactory::new()->many(3),
        ]);

        $this->assertCount(3, $post->getRelatedToPosts());
        SpecificPostFactory::assert()->count(4);
        PostFactory::assert()->count(4); // 4 posts with the specific ones
    }

    /**
     * @test
     */
    public function inverse_many_to_many_with_nested_collection_relationship_polymorphic_mixed(): void
    {
        $post = SpecificPostFactory::createOne([
            'relatedToPosts' => [PostFactory::new(), SpecificPostFactory::new()],
        ]);

        $this->assertCount(2, $post->getRelatedToPosts());
        SpecificPostFactory::assert()->count(2);
        PostFactory::assert()->count(3); // 3 posts with the specific ones
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
        TagFactory::assert()->count(
            \getenv('USE_FOUNDRY_BUNDLE')
                ? 8  // 6 created by this test and 2 in global state
                : 6
        );
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
        TagFactory::assert()->count(
            \getenv('USE_FOUNDRY_BUNDLE')
                ? 2  // 2 created in global state
                : 0
        );
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

        self::container()->get(EntityManagerInterface::class)->clear();

        $post = PostFactory::createOne(['category' => $category]);

        $this->assertSame('My Category', $post->getCategory()->getName());
    }

    /**
     * @test
     */
    public function many_to_one_unmanaged_raw_entity(): void
    {
        $category = CategoryFactory::createOne(['name' => 'My Category'])->object();

        self::container()->get(EntityManagerInterface::class)->clear();

        $post = PostFactory::createOne(['category' => $category]);

        $this->assertSame('My Category', $post->getCategory()->getName());
    }

    /**
     * @test
     */
    public function factory_with_embeddable(): void
    {
        ContactFactory::repository()->assert()->empty();

        $object = ContactFactory::createOne();

        ContactFactory::repository()->assert()->count(1);
        $this->assertSame('Sally', $object->getName());
        $this->assertSame('Some address', $object->getAddress()->getValue());
    }

    /**
     * @test
     */
    public function embeddables_are_never_persisted(): void
    {
        $object1 = AddressFactory::createOne();
        $object2 = AddressFactory::createOne(['value' => 'another address']);

        $this->assertSame('Some address', $object1->getValue());
        $this->assertSame('another address', $object2->getValue());
    }

    protected function categoryClass(): string
    {
        return Category::class;
    }

    protected function categoryFactoryClass(): string
    {
        return CategoryFactory::class;
    }
}
