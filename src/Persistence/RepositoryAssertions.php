<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Doctrine\Persistence\ObjectRepository;
use Zenstruck\Assert;
use Zenstruck\Foundry\Factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type Parameters from Factory
 */
final class RepositoryAssertions
{
    /**
     * @internal
     *
     * @param RepositoryDecorator<object,ObjectRepository<object>> $repository
     */
    public function __construct(private RepositoryDecorator $repository)
    {
    }

    /**
     * @param Parameters $criteria
     */
    public function empty(array $criteria = [], string $message = 'Expected {entity} repository to be empty but it has {actual} items.'): self
    {
        return $this->count(0, $criteria, $message);
    }

    /**
     * @param Parameters $criteria
     */
    public function notEmpty(array $criteria = [], string $message = 'Expected {entity} repository to NOT be empty but it is.'): self
    {
        Assert::that($this->repository->count($criteria))
            ->isNot(0, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    /**
     * @param Parameters $criteria
     */
    public function count(int $expectedCount, array $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be {expected}.'): self
    {
        Assert::that($this->repository->count($criteria))
            ->is($expectedCount, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    /**
     * @param Parameters $criteria
     */
    public function countGreaterThan(int $expected, array $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be greater than {expected}.'): self
    {
        Assert::that($this->repository->count($criteria))
            ->isGreaterThan($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    /**
     * @param Parameters $criteria
     */
    public function countGreaterThanOrEqual(int $expected, array $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be greater than or equal to {expected}.'): self
    {
        Assert::that($this->repository->count($criteria))
            ->isGreaterThanOrEqualTo($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    /**
     * @param Parameters $criteria
     */
    public function countLessThan(int $expected, array $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be less than {expected}.'): self
    {
        Assert::that($this->repository->count($criteria))
            ->isLessThan($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    /**
     * @param Parameters $criteria
     */
    public function countLessThanOrEqual(int $expected, array $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be less than or equal to {expected}.'): self
    {
        Assert::that($this->repository->count($criteria))
            ->isLessThanOrEqualTo($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    public function exists(mixed $criteria, string $message = 'Expected {entity} to exist but it does not.'): self
    {
        Assert::that($this->repository->find($criteria))->isNotEmpty($message, [
            'entity' => $this->repository->getClassName(),
            'criteria' => $criteria,
        ]);

        return $this;
    }

    public function notExists(mixed $criteria, string $message = 'Expected {entity} to not exist but it does.'): self
    {
        Assert::that($this->repository->find($criteria))->isEmpty($message, [
            'entity' => $this->repository->getClassName(),
            'criteria' => $criteria,
        ]);

        return $this;
    }
}
