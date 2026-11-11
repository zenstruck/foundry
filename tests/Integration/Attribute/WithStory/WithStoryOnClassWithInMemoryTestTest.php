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
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\BuildCountingStory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[WithStory(BuildCountingStory::class)]
#[ResetDatabase]
#[RequiresEnvironmentVariable('DATABASE_URL')]
#[RequiresEnvironmentVariable('MONGO_URL', '')]
#[RequiresEnvironmentVariable('USE_DAMA_DOCTRINE_TEST_BUNDLE', '1')]
#[RequiresMethod(StaticDriver::class, 'createSavepoint')]
final class WithStoryOnClassWithInMemoryTestTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        BuildCountingStory::$builds = 0;
    }

    #[Test]
    public function story_is_shared_by_the_tests_using_the_database(): void
    {
        self::assertSame(1, BuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(1);
    }

    #[Test]
    #[Depends('story_is_shared_by_the_tests_using_the_database')]
    #[AsInMemoryTest]
    public function an_in_memory_test_builds_its_own_story(): void
    {
        self::assertSame(2, BuildCountingStory::$builds);

        $foo = BuildCountingStory::get('foo');
        self::assertInstanceOf(GenericEntity::class, $foo);
        self::assertNull($foo->id);
    }

    #[Test]
    #[Depends('an_in_memory_test_builds_its_own_story')]
    public function the_next_test_gets_the_shared_story_again(): void
    {
        self::assertSame(2, BuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(1);

        $foo = BuildCountingStory::get('foo');
        self::assertInstanceOf(GenericEntity::class, $foo);
        self::assertNotNull($foo->id);
    }
}
