<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase as ResetDatabaseAttribute;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\PHPUnit\AttributeReader;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ResetDatabase
{
    /**
     * @internal
     * @beforeClass
     */
    #[BeforeClass]
    public static function _resetDatabaseBeforeFirstTest(): void
    {
        if (FoundryExtension::isEnabled()) {
            trigger_deprecation('zenstruck/foundry', '2.9', \sprintf('Trait "%s" is deprecated and will be removed in Foundry 3. Use attribute "%s" instead. See https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.9.md to upgrade.', ResetDatabase::class, ResetDatabaseAttribute::class));

            if (self::_classHasResetDatabaseAttribute()) {
                return;
            }
        }

        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore function.alreadyNarrowedType
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        ResetDatabaseManager::resetBeforeFirstTest(
            static fn() => static::bootKernel(),
            static fn() => static::ensureKernelShutdown(),
        );
    }

    /**
     * @internal
     * @before
     */
    #[Before(10)]
    public static function _resetDatabaseBeforeEachTest(): void
    {
        if (FoundryExtension::isEnabled() && self::_classHasResetDatabaseAttribute()) {
            return;
        }

        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore function.alreadyNarrowedType
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        ResetDatabaseManager::resetBeforeEachTest(
            static fn() => static::bootKernel(),
            static fn() => static::ensureKernelShutdown(),
        );
    }

    /**
     * @internal
     */
    private static function _classHasResetDatabaseAttribute(): bool
    {
        $resetDatabaseAttributes = AttributeReader::collectAttributesFromClassAndParents(
            ResetDatabaseAttribute::class,
            new \ReflectionClass(static::class)
        );

        return [] !== $resetDatabaseAttributes;
    }
}
