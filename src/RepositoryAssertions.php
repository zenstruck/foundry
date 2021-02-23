<?php

namespace Zenstruck\Foundry;

use PHPUnit\Framework\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RepositoryAssertions
{
    private $repository;

    public function __construct(RepositoryProxy $repository)
    {
        $this->repository = $repository;
    }

    public function empty(string $message = ''): self
    {
        return $this->count(0, $message);
    }

    public function count(int $expectedCount, string $message = ''): self
    {
        Assert::assertSame($expectedCount, $this->repository->count(), $message);

        return $this;
    }

    public function countGreaterThan(int $expected, string $message = ''): self
    {
        Assert::assertGreaterThan($expected, $this->repository->count(), $message);

        return $this;
    }

    public function countGreaterThanOrEqual(int $expected, string $message = ''): self
    {
        Assert::assertGreaterThanOrEqual($expected, $this->repository->count(), $message);

        return $this;
    }

    public function countLessThan(int $expected, string $message = ''): self
    {
        Assert::assertLessThan($expected, $this->repository->count(), $message);

        return $this;
    }

    public function countLessThanOrEqual(int $expected, string $message = ''): self
    {
        Assert::assertLessThanOrEqual($expected, $this->repository->count(), $message);

        return $this;
    }

    /**
     * @param object|array|mixed $criteria
     */
    public function exists($criteria, string $message = ''): self
    {
        Assert::assertNotNull($this->repository->find($criteria), $message);

        return $this;
    }

    /**
     * @param object|array|mixed $criteria
     */
    public function notExists($criteria, string $message = ''): self
    {
        Assert::assertNull($this->repository->find($criteria), $message);

        return $this;
    }
}
