<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Zenstruck\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RepositoryAssertions
{
    private static string $countWithoutCriteriaDeprecationMessagePattern = 'Passing the message to %s() as second parameter is deprecated. Use third parameter.';

    public function __construct(private RepositoryProxy $repository)
    {
    }

    public function empty(array|string $criteria = [], string $message = 'Expected {entity} repository to be empty but it has {actual} items.'): self
    {
        if (\is_string($criteria)) {
            trigger_deprecation('zenstruck/foundry', '1.26', 'Passing the message to %s() as first parameter is deprecated. Use second parameter.', __METHOD__);

            $message = $criteria;
        }

        return $this->count(0, \is_array($criteria) ? $criteria : [], $message);
    }

    public function count(int $expectedCount, array|string $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be {expected}.'): self
    {
        if (\is_string($criteria)) {
            trigger_deprecation('zenstruck/foundry', '1.26', self::$countWithoutCriteriaDeprecationMessagePattern, __METHOD__);

            $message = $criteria;
        }

        Assert::that($this->repository->count(\is_array($criteria) ? $criteria : []))
            ->is($expectedCount, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    public function countGreaterThan(int $expected, array|string $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be greater than {expected}.'): self
    {
        if (\is_string($criteria)) {
            trigger_deprecation('zenstruck/foundry', '1.26', self::$countWithoutCriteriaDeprecationMessagePattern, __METHOD__);

            $message = $criteria;
        }

        Assert::that($this->repository->count(\is_array($criteria) ? $criteria : []))
            ->isGreaterThan($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    public function countGreaterThanOrEqual(int $expected, array|string $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be greater than or equal to {expected}.'): self
    {
        if (\is_string($criteria)) {
            trigger_deprecation('zenstruck/foundry', '1.26', self::$countWithoutCriteriaDeprecationMessagePattern, __METHOD__);

            $message = $criteria;
        }

        Assert::that($this->repository->count(\is_array($criteria) ? $criteria : []))
            ->isGreaterThanOrEqualTo($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    public function countLessThan(int $expected, array|string $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be less than {expected}.'): self
    {
        if (\is_string($criteria)) {
            trigger_deprecation('zenstruck/foundry', '1.26', self::$countWithoutCriteriaDeprecationMessagePattern, __METHOD__);

            $message = $criteria;
        }

        Assert::that($this->repository->count(\is_array($criteria) ? $criteria : []))
            ->isLessThan($expected, $message, ['entity' => $this->repository->getClassName()])
        ;

        return $this;
    }

    public function countLessThanOrEqual(int $expected, array|string $criteria = [], string $message = 'Expected count of {entity} repository ({actual}) to be less than or equal to {expected}.'): self
    {
        if (\is_string($criteria)) {
            trigger_deprecation('zenstruck/foundry', '1.26', self::$countWithoutCriteriaDeprecationMessagePattern, __METHOD__);

            $message = $criteria;
        }

        Assert::that($this->repository->count(\is_array($criteria) ? $criteria : []))
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
