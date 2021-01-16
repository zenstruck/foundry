<?php

namespace Zenstruck\Foundry;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Assert;

/**
 * @mixin EntityRepository<TProxiedObject>
 * @template TProxiedObject of object
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RepositoryProxy implements ObjectRepository, \IteratorAggregate, \Countable
{
    /** @var ObjectRepository<TProxiedObject>|EntityRepository<TProxiedObject> */
    private $repository;

    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __call(string $method, array $arguments)
    {
        return $this->proxyResult($this->repository->{$method}(...$arguments));
    }

    public function count(): int
    {
        if ($this->repository instanceof EntityRepository) {
            // use query to avoid loading all entities
            return $this->repository->count([]);
        }

        if ($this->repository instanceof \Countable) {
            return \count($this->repository);
        }

        return \count($this->findAll());
    }

    public function getIterator(): \Traversable
    {
        // TODO: $this->repository is set to ObjectRepository, which is not
        //       iterable. Can this every be another RepositoryProxy?
        if (\is_iterable($this->repository)) {
            return yield from $this->repository;
        }

        yield from $this->findAll();
    }

    /**
     * @deprecated use RepositoryProxy::count()
     */
    public function getCount(): int
    {
        trigger_deprecation('zenstruck\foundry', '1.5.0', 'Using RepositoryProxy::getCount() is deprecated, use RepositoryProxy::count() (it is now Countable).');

        return $this->count();
    }

    public function assertEmpty(string $message = ''): self
    {
        return $this->assertCount(0, $message);
    }

    public function assertCount(int $expectedCount, string $message = ''): self
    {
        Assert::assertSame($expectedCount, $this->count(), $message);

        return $this;
    }

    public function assertCountGreaterThan(int $expected, string $message = ''): self
    {
        Assert::assertGreaterThan($expected, $this->count(), $message);

        return $this;
    }

    public function assertCountGreaterThanOrEqual(int $expected, string $message = ''): self
    {
        Assert::assertGreaterThanOrEqual($expected, $this->count(), $message);

        return $this;
    }

    public function assertCountLessThan(int $expected, string $message = ''): self
    {
        Assert::assertLessThan($expected, $this->count(), $message);

        return $this;
    }

    public function assertCountLessThanOrEqual(int $expected, string $message = ''): self
    {
        Assert::assertLessThanOrEqual($expected, $this->count(), $message);

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
     *
     * @psalm-return Proxy<TProxiedObject>|null
     */
    public function first(string $sortedField = 'id'): ?Proxy
    {
        return $this->findBy([], [$sortedField => 'ASC'], 1)[0] ?? null;
    }

    /**
     * @return Proxy|object|null
     *
     * @psalm-return Proxy<TProxiedObject>|null
     */
    public function last(string $sortedField = 'id'): ?Proxy
    {
        return $this->findBy([], [$sortedField => 'DESC'], 1)[0] ?? null;
    }

    /**
     * Remove all rows.
     */
    public function truncate(): void
    {
        $om = Factory::configuration()->objectManagerFor($this->getClassName());

        if ($om instanceof EntityManagerInterface) {
            $om->createQuery("DELETE {$this->getClassName()} e")->execute();

            return;
        }

        foreach ($this as $object) {
            $om->remove($object);
        }

        $om->flush();
    }

    /**
     * Fetch one random object.
     *
     * @param array $attributes The findBy criteria
     *
     * @return Proxy|object
     *
     * @throws \RuntimeException if no objects are persisted
     *
     * @psalm-return Proxy<TProxiedObject>
     */
    public function random(array $attributes = []): Proxy
    {
        return $this->randomSet(1, $attributes)[0];
    }

    /**
     * Fetch a random set of objects.
     *
     * @param int   $number     The number of objects to return
     * @param array $attributes The findBy criteria
     *
     * @return Proxy[]|object[]
     *
     * @throws \RuntimeException         if not enough persisted objects to satisfy the number requested
     * @throws \InvalidArgumentException if number is less than zero
     */
    public function randomSet(int $number, array $attributes = []): array
    {
        if ($number < 0) {
            throw new \InvalidArgumentException(\sprintf('$number must be positive (%d given).', $number));
        }

        return $this->randomRange($number, $number, $attributes);
    }

    /**
     * Fetch a random range of objects.
     *
     * @param int   $min        The minimum number of objects to return
     * @param int   $max        The maximum number of objects to return
     * @param array $attributes The findBy criteria
     *
     * @return Proxy[]|object[]
     *
     * @throws \RuntimeException         if not enough persisted objects to satisfy the max
     * @throws \InvalidArgumentException if min is less than zero
     * @throws \InvalidArgumentException if max is less than min
     */
    public function randomRange(int $min, int $max, array $attributes = []): array
    {
        if ($min < 0) {
            throw new \InvalidArgumentException(\sprintf('$min must be positive (%d given).', $min));
        }

        if ($max < $min) {
            throw new \InvalidArgumentException(\sprintf('$max (%d) cannot be less than $min (%d).', $max, $min));
        }

        $all = \array_values($this->findBy($attributes));

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
     *
     * @psalm-param Proxy<TProxiedObject>|array|mixed $criteria
     * @psalm-return Proxy<TProxiedObject>|list<Proxy<TProxiedObject>>|null
     */
    public function find($criteria)
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
     * @param array|null $orderBy Some ObjectRepository's (ie Doctrine\ORM\EntityRepository) add this optional parameter
     *
     * @return Proxy|object|null
     *
     * @throws \RuntimeException if the wrapped ObjectRepository does not have the $orderBy parameter
     *
     * @psalm-return Proxy<TProxiedObject>|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?Proxy
    {
        if (\is_array($orderBy)) {
            $wrappedParams = (new \ReflectionClass($this->repository))->getMethod('findOneBy')->getParameters();

            if (!isset($wrappedParams[1]) || 'orderBy' !== $wrappedParams[1]->getName() || !$wrappedParams[1]->getType() instanceof \ReflectionNamedType || 'array' !== $wrappedParams[1]->getType()->getName()) {
                throw new \RuntimeException(\sprintf('Wrapped repository\'s (%s) findOneBy method does not have an $orderBy parameter.', \get_class($this->repository)));
            }
        }

        $result = $this->repository->findOneBy(self::normalizeCriteria($criteria), $orderBy);
        if (null === $result) {
            return null;
        }

        return $this->proxyResult($result);
    }

    /**
     * @psalm-return class-string<TProxiedObject>
     */
    public function getClassName(): string
    {
        return $this->repository->getClassName();
    }

    /**
     * @param mixed $result
     *
     * @return Proxy|Proxy[]|object|object[]|mixed
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     * @template TResult of object
     * @psalm-param TResult|list<TResult> $result
     * @psalm-return ($result is array ? list<Proxy<TResult>> : Proxy<TResult>)
     */
    private function proxyResult($result)
    {
        if (\is_a($result, $this->getClassName())) {
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
