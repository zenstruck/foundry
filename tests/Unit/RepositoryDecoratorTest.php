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

use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RepositoryDecoratorTest extends TestCase
{
    /**
     * @test
     * @dataProvider objectRepositoryWithoutFindOneByOrderBy
     */
    public function calling_find_one_by_with_order_by_when_wrapped_repo_does_not_have_throws_exception(ObjectRepository $inner): void
    {
        $proxy = new RepositoryDecorator($inner);

        $this->expectException(\RuntimeException::class);

        $proxy->findOneBy([], ['id' => 'DESC']);
    }

    public static function objectRepositoryWithoutFindOneByOrderBy(): iterable
    {
        yield [new RepositoryDecorator(new class() extends RepositoryStub {
            public function findOneBy(array $criteria): void
            {
            }
        })];

        yield [new RepositoryDecorator(new class() extends RepositoryStub {
            public function findOneBy(array $criteria, ?array $foo = null): void
            {
            }
        })];

        yield [new RepositoryDecorator(new class() extends RepositoryStub {
            public function findOneBy(array $criteria, $orderBy = null): void
            {
            }
        })];

        yield [new RepositoryDecorator(new class() extends RepositoryStub {
            public function findOneBy(array $criteria, ?string $orderBy = null): void
            {
            }
        })];
    }

    /**
     * @test
     */
    public function can_get_inner_repository(): void
    {
        $inner = $this->createMock(ObjectRepository::class);

        $repository = new RepositoryDecorator($inner);

        $this->assertSame($inner, $repository->inner());
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
