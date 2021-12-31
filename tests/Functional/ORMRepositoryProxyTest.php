<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Doctrine\Common\Proxy\Proxy as DoctrineProxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use function Zenstruck\Foundry\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ORMRepositoryProxyTest extends RepositoryProxyTest
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
    public function functions_calls_are_passed_to_underlying_repository(): void
    {
        $this->assertSame('from custom method', repository(Post::class)->customMethod());
    }

    /**
     * @see https://github.com/zenstruck/foundry/issues/42
     *
     * @test
     */
    public function doctrine_proxies_are_converted_to_foundry_proxies(): void
    {
        PostFactory::createOne(['category' => CategoryFactory::new()]);

        // clear the em so nothing is tracked
        static::$kernel->getContainer()->get('doctrine')->getManager()->clear();

        // load a random Post which causes the em to track a "doctrine proxy" for category
        PostFactory::random();

        // load a random Category which should be a "doctrine proxy"
        $category = CategoryFactory::random()->object();

        // ensure the category is a "doctrine proxy" and a Category
        $this->assertInstanceOf(DoctrineProxy::class, $category);
        $this->assertInstanceOf(Category::class, $category);
    }

    /**
     * @test
     */
    public function proxy_wrapping_orm_entity_manager_can_order_by_in_find_one_by(): void
    {
        $categoryA = CategoryFactory::createOne();
        $categoryB = CategoryFactory::createOne();
        $categoryC = CategoryFactory::createOne();

        $this->assertSame($categoryC->getId(), CategoryFactory::repository()->findOneBy([], ['id' => 'DESC'])->getId());
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
