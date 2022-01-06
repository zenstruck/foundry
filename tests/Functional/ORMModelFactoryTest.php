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
