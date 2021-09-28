<?php

namespace Zenstruck\Foundry;

use Zenstruck\Assert;

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

    public function empty(string $message = 'Expected {entity} repository to be empty but it has {actual} items.'): self
    {
        return $this->count(0, $message);
    }

    public function count(int $expectedCount, string $message = 'Expected count of {entity} repository ({actual}) to be {expected}.'): self
    {
        Assert::that($this->repository)
            ->hasCount($expectedCount, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    public function countGreaterThan(int $expected, string $message = 'Expected count of {entity} repository ({actual}) to be greater than {expected}.'): self
    {
        Assert::that($this->repository->count())
            ->isGreaterThan($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    public function countGreaterThanOrEqual(int $expected, string $message = 'Expected count of {entity} repository ({actual}) to be greater than or equal to {expected}.'): self
    {
        Assert::that($this->repository->count())
            ->isGreaterThanOrEqualTo($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    public function countLessThan(int $expected, string $message = 'Expected count of {entity} repository ({actual}) to be less than {expected}.'): self
    {
        Assert::that($this->repository->count())
            ->isLessThan($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    public function countLessThanOrEqual(int $expected, string $message = 'Expected count of {entity} repository ({actual}) to be less than or equal to {expected}.'): self
    {
        Assert::that($this->repository->count())
            ->isLessThanOrEqualTo($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    /**
     * @param object|array|mixed $criteria
     */
    public function exists($criteria, string $message = 'Expected {entity} to exist but it does not.'): self
    {
        Assert::that($this->repository->find($criteria))->isNotEmpty($message, [
            'entity' => $this->repository->getClassName(),
            'criteria' => $criteria,
        ]);

        return $this;
    }

    /**
     * @param object|array|mixed $criteria
     */
    public function notExists($criteria, string $message = 'Expected {entity} to not exist but it does.'): self
    {
        Assert::that($this->repository->find($criteria))->isEmpty($message, [
            'entity' => $this->repository->getClassName(),
            'criteria' => $criteria,
        ]);

        return $this;
    }
}
