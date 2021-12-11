<?php

namespace Zenstruck\Foundry\Tests\Unit;

use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RepositoryProxyTest extends TestCase
{
    /**
     * @test
     * @dataProvider objectRepositoryWithoutFindOneByOrderBy
     */
    public function calling_find_one_by_with_order_by_when_wrapped_repo_does_not_have_throws_exception(ObjectRepository $inner): void
    {
        $proxy = new RepositoryProxy($inner);

        $this->expectException(\RuntimeException::class);

        $proxy->findOneBy([], ['id' => 'DESC']);
    }

    public static function objectRepositoryWithoutFindOneByOrderBy(): iterable
    {
        yield [new RepositoryProxy(new class() extends RepositoryStub {
            public function findOneBy(array $criteria): ?object
            {
            }
        })];

        yield [new RepositoryProxy(new class() extends RepositoryStub {
            public function findOneBy(array $criteria, ?array $foo = null): ?object
            {
            }
        })];

        yield [new RepositoryProxy(new class() extends RepositoryStub {
            public function findOneBy(array $criteria, $orderBy = null): ?object
            {
            }
        })];

        yield [new RepositoryProxy(new class() extends RepositoryStub {
            public function findOneBy(array $criteria, ?string $orderBy = null): ?object
            {
            }
        })];
    }
}

abstract class RepositoryStub implements ObjectRepository
{
    public function find($id): ?object
    {
    }

    public function findAll(): array
    {
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
    }

    public function getClassName(): string
    {
    }
}
