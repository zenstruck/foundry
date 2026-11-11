<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Attribute\WithStory;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\BuildCountingStory;

/**
 * A test skipping DAMA's rollback does not use the connection holding the shared stories:
 * the stories of such a class are loaded before each test.
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[WithStory(BuildCountingStory::class)]
#[ResetDatabase]
#[RequiresEnvironmentVariable('DATABASE_URL')]
#[RequiresEnvironmentVariable('MONGO_URL', '')]
#[RequiresEnvironmentVariable('USE_DAMA_DOCTRINE_TEST_BUNDLE', '1')]
#[RequiresMethod(StaticDriver::class, 'createSavepoint')]
final class WithStoryOnClassWithSkipDatabaseRollbackTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        BuildCountingStory::$builds = 0;
    }

    #[Test]
    public function story_is_built_for_each_test(): void
    {
        self::assertSame(1, BuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(1);
    }

    #[Test]
    #[Depends('story_is_built_for_each_test')]
    #[SkipDatabaseRollback]
    public function including_the_test_skipping_the_rollback(): void
    {
        try {
            self::assertSame(2, BuildCountingStory::$builds);
            GenericEntityFactory::assert()->count(1);
        } finally {
            GenericEntityFactory::truncate(); // it was committed
        }
    }

    #[Test]
    #[Depends('including_the_test_skipping_the_rollback')]
    public function and_the_test_following_it(): void
    {
        self::assertSame(3, BuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(1);
    }
}
