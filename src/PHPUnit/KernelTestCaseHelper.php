<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\PHPUnit;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @internal
 */
final class KernelTestCaseHelper
{
    /**
     * @param class-string $class
     */
    public static function getContainerForTestClass(string $class): Container
    {
        if (!\is_subclass_of($class, KernelTestCase::class)) {
            throw new \LogicException(\sprintf('Class "%s" must extend "%s".', $class, KernelTestCase::class));
        }

        return (\Closure::bind(
            fn() => $class::getContainer(),
            newThis: null,
            newScope: $class,
        ))();
    }

    /**
     * @param class-string $class
     */
    public static function tearDownClass(string $class): void
    {
        if (!\is_subclass_of($class, TestCase::class)) {
            throw new \LogicException(\sprintf('Class "%s" must extend "%s".', $class, TestCase::class));
        }

        (\Closure::bind(
            fn() => $class::tearDownAfterClass(),
            newThis: null,
            newScope: $class,
        ))();
    }
}
