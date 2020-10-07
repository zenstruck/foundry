<?php

namespace Zenstruck\Foundry;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Assert;

/**
 * @mixin EntityRepository
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RepositoryProxy implements ObjectRepository
{
    /** @var ObjectRepository */
    private $repository;

    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __call(string $method, array $arguments)
    {
        return $this->proxyResult($this->repository->{$method}(...$arguments));
    }

    public function getCount(): int
    {
        if ($this->repository instanceof EntityRepository) {
            return $this->repository->count([]);
        }

        return \count($this->findAll());
    }

    public function assertEmpty(string $message = ''): self
    {
        return $this->assertCount(0, $message);
    }

    public function assertCount(int $expectedCount, string $message = ''): self
    {
        Assert::assertSame($expectedCount, $this->getCount(), $message);

        return $this;
    }

    public function assertCountGreaterThan(int $expected, string $message = ''): self
    {
        Assert::assertGreaterThan($expected, $this->getCount(), $message);

        return $this;
    }

    public function assertCountGreaterThanOrEqual(int $expected, string $message = ''): self
    {
        Assert::assertGreaterThanOrEqual($expected, $this->getCount(), $message);

        return $this;
    }

    public function assertCountLessThan(int $expected, string $message = ''): self
    {
        Assert::assertLessThan($expected, $this->getCount(), $message);

        return $this;
    }

    public function assertCountLessThanOrEqual(int $expected, string $message = ''): self
    {
        Assert::assertLessThanOrEqual($expected, $this->getCount(), $message);

        return $this;
    }

    /**
     * @param object|array|mixed $criteria
     */
    public function assertExists($criteria, string $message = ''): self
    {
        Assert::assertNotNull($this->find($criteria), $message);

        return $this;
    }

    /**
     * @param object|array|mixed $criteria
     */
    public function assertNotExists($criteria, string $message = ''): self
    {
        Assert::assertNull($this->find($criteria), $message);

        return $this;
    }

    /**
     * @return Proxy|object|null
     */
    public function first(): ?Proxy
    {
        return $this->findOneBy([]);
    }

    /**
     * Remove all rows.
     */
    public function truncate(): void
    {
        $om = Factory::configuration()->objectManagerFor($this->getClassName());

        if (!$om instanceof EntityManagerInterface) {
            throw new \RuntimeException('This operation is only available when using doctrine/orm');
        }

        $om->createQuery("DELETE {$this->getClassName()} e")->execute();
    }

    /**
     * Fetch one random object.
     *
     * @return Proxy|object
     *
     * @throws \RuntimeException if no objects are persisted
     */
    public function random(): Proxy
    {
        return $this->randomSet(1)[0];
    }

    /**
     * Fetch a random set of objects.
     *
     * @param int $number The number of objects to return
     *
     * @return Proxy[]|object[]
     *
     * @throws \RuntimeException         if not enough persisted objects to satisfy the number requested
     * @throws \InvalidArgumentException if number is less than zero
     */
    public function randomSet(int $number): array
    {
        if ($number < 0) {
            throw new \InvalidArgumentException(\sprintf('$number must be positive (%d given).', $number));
        }

        return $this->randomRange($number, $number);
    }

    /**
     * Fetch a random range of objects.
     *
     * @param int $min The minimum number of objects to return
     * @param int $max The maximum number of objects to return
     *
     * @return Proxy[]|object[]
     *
     * @throws \RuntimeException         if not enough persisted objects to satisfy the max
     * @throws \InvalidArgumentException if min is less than zero
     * @throws \InvalidArgumentException if max is less than min
     */
    public function randomRange(int $min, int $max): array
    {
        if ($min < 0) {
            throw new \InvalidArgumentException(\sprintf('$min must be positive (%d given).', $min));
        }

        if ($max < $min) {
            throw new \InvalidArgumentException(\sprintf('$max (%d) cannot be less than $min (%d).', $max, $min));
        }

        $all = \array_values($this->findAll());

        \shuffle($all);

        if (\count($all) < $max) {
            throw new \RuntimeException(\sprintf('At least %d "%s" object(s) must have been persisted (%d persisted).', $max, $this->getClassName(), \count($all)));
        }

        return \array_slice($all, 0, \random_int($min, $max));
    }

    /**
     * @param object|array|mixed $criteria
     *
     * @return Proxy|object|null
     */
    public function find($criteria): ?Proxy
    {
        if ($criteria instanceof Proxy) {
            $criteria = $criteria->object();
        }

        if (!\is_array($criteria)) {
            return $this->proxyResult($this->repository->find($criteria));
        }

        return $this->findOneBy($criteria);
    }

    /**
     * @return Proxy[]|object[]
     */
    public function findAll(): array
    {
        return $this->proxyResult($this->repository->findAll());
    }

    /**
     * @return Proxy[]|object[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->proxyResult($this->repository->findBy(self::normalizeCriteria($criteria), $orderBy, $limit, $offset));
    }

    /**
     * @return Proxy|object|null
     */
    public function findOneBy(array $criteria): ?Proxy
    {
        return $this->proxyResult($this->repository->findOneBy(self::normalizeCriteria($criteria)));
    }

    public function getClassName(): string
    {
        return $this->repository->getClassName();
    }

    /**
     * @param mixed $result
     *
     * @return Proxy|Proxy[]|object|object[]|mixed
     */
    private function proxyResult($result)
    {
        if (\is_object($result) && \is_a($result, $this->getClassName())) {
            return Proxy::createFromPersisted($result);
        }

        if (\is_array($result)) {
            return \array_map([$this, 'proxyResult'], $result);
        }

        return $result;
    }

    private static function normalizeCriteria(array $criteria): array
    {
        return \array_map(
            function($value) {
                return $value instanceof Proxy ? $value->object() : $value;
            },
            $criteria
        );
    }
}
