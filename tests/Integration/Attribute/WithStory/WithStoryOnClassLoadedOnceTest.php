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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\FakerAdapter;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\BuildCountingStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\DataProviderBuildCountingStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\OtherBuildCountingStory;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\save;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[WithStory(BuildCountingStory::class)]
#[WithStory(OtherBuildCountingStory::class)]
#[ResetDatabase]
#[RequiresEnvironmentVariable('DATABASE_URL')]
#[RequiresEnvironmentVariable('MONGO_URL', '')]
#[RequiresEnvironmentVariable('USE_DAMA_DOCTRINE_TEST_BUNDLE', '1')]
#[RequiresMethod(StaticDriver::class, 'createSavepoint')]
final class WithStoryOnClassLoadedOnceTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        BuildCountingStory::$builds = OtherBuildCountingStory::$builds = DataProviderBuildCountingStory::$builds = 0;
    }

    #[Test]
    public function stories_are_built_once_for_the_class(): void
    {
        self::assertSame(1, BuildCountingStory::$builds);
        self::assertSame(1, OtherBuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(2);
    }

    #[Test]
    #[Depends('stories_are_built_once_for_the_class')]
    #[WithStory(BuildCountingStory::class)]
    public function stories_are_not_built_again_even_when_also_declared_on_the_method(): void
    {
        self::assertSame(1, BuildCountingStory::$builds);
        self::assertSame(1, OtherBuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(2);
    }

    #[Test]
    #[Depends('stories_are_not_built_again_even_when_also_declared_on_the_method')]
    #[WithStory(EntityStory::class)]
    public function a_story_only_declared_on_the_method_is_loaded_for_this_test_only(): void
    {
        GenericEntityFactory::assert()->count(4);
    }

    #[Test]
    #[Depends('a_story_only_declared_on_the_method_is_loaded_for_this_test_only')]
    public function changes_made_by_a_test(): void
    {
        $foo = BuildCountingStory::get('foo');
        self::assertInstanceOf(GenericEntity::class, $foo);
        $foo->setProp1('changed');
        save($foo);

        GenericEntityFactory::createOne();

        GenericEntityFactory::assert()->count(3);
    }

    #[Test]
    #[Depends('changes_made_by_a_test')]
    public function are_rolled_back_before_the_next_one(): void
    {
        self::assertSame(1, BuildCountingStory::$builds);
        self::assertSame(1, OtherBuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(2);

        self::assertSame('foo', BuildCountingStory::get('foo')->getProp1());
    }

    #[Test]
    #[DataProvider('dataSets')]
    public function class_stories_are_shared_with_the_data_sets(int $dataSet): void
    {
        self::assertSame(1, BuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(2);

        GenericEntityFactory::createOne(); // rolled back before the next data set
    }

    #[Test]
    #[DataProvider('dataSets')]
    #[WithStory(DataProviderBuildCountingStory::class)]
    public function stories_of_a_data_provider_method_are_built_once_for_its_data_sets(int $dataSet): void
    {
        self::assertSame(1, DataProviderBuildCountingStory::$builds);
        self::assertSame(1, BuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(3);

        GenericEntityFactory::createOne(); // rolled back before the next data set
    }

    #[Test]
    #[Depends('stories_of_a_data_provider_method_are_built_once_for_its_data_sets')]
    public function stories_of_a_data_provider_method_are_rolled_back_after_it(): void
    {
        self::assertSame(1, BuildCountingStory::$builds);
        GenericEntityFactory::assert()->count(2);
        self::assertSame('foo', BuildCountingStory::get('foo')->getProp1());
    }

    #[Test]
    public function stories_are_built_with_the_faker_seed_of_their_scope(): void
    {
        FakerAdapter::reset();
        FakerAdapter::setCurrentTestId(self::class);

        self::assertSame(faker()->randomNumber(), BuildCountingStory::$firstFakerNumber);
    }

    /**
     * @return iterable<array{int}>
     */
    public static function dataSets(): iterable
    {
        yield [1];
        yield [2];
        yield [3];
    }
}
