<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures\FixtureStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures\FixtureStoryWithNameCollision;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\repository;

final class LoadStoryCommandTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_no_story_marked_as_fixture(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No story as fixture available');

        $this->commandTester()->execute(['name' => 'foo']);
    }

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_story_does_not_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Story with name "invalid-name" does not exist');

        $this->commandTester(['environment' => 'stories_as_fixtures'])->execute(['name' => 'invalid-name', '--append' => true]);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_a_story(): void
    {
        $this->commandTester(['environment' => 'stories_as_fixtures'])->execute(['name' => 'fixture-story', '--append' => true]);

        GenericEntityFactory::assert()->count(1);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_a_story_with_verbose_mode(): void
    {
        $commandTester = $this->commandTester(['environment' => 'stories_as_fixtures']);
        $commandTester->execute(['name' => 'fixture-story', '--append' => true], ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]);

        GenericEntityFactory::assert()->count(1);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);

        self::assertStringContainsString('loaded (name: fixture-story)', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_name_collision_between_two_stories_name(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Cannot use #[AsFixture] name "fixture-story" for service "%s". This name is already used by service "%s".',
                FixtureStoryWithNameCollision::class,
                FixtureStory::class,
            )
        );

        $this->commandTester(['environment' => 'story_fixture_with_name_collision'])->execute(['name' => 'fixture-story', '--append' => true]);
    }

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_name_collision_between_story_name_and_group_name(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot use #[AsFixture] group(s) "fixture-story", they collide with fixture names.');

        $this->commandTester(['environment' => 'story_fixture_with_group_name_collision'])->execute(['name' => 'fixture-story', '--append' => true]);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_one_single_story_based_on_its_group_name(): void
    {
        $this->commandTester(['environment' => 'stories_as_fixtures'])->execute(['name' => 'single-fixture-in-group', '--append' => true]);

        GenericEntityFactory::assert()->count(1);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_multiple_stories_based_on_their_group_name(): void
    {
        $this->commandTester(['environment' => 'stories_as_fixtures'])->execute(['name' => 'multiple-fixtures-in-group', '--append' => true]);

        GenericEntityFactory::assert()->count(2);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story-for-group']);
    }

    /**
     * @test
     * @dataProvider provideFixturesWhichLoadAnotherFixtureCases
     */
    #[Test]
    #[DataProvider('provideFixturesWhichLoadAnotherFixtureCases')]
    public function it_can_load_fixture_which_loads_another_fixture(string $name): void
    {
        $this->commandTester(['environment' => 'stories_as_fixtures'])->execute(['name' => $name, '--append' => true]);

        GenericEntityFactory::assert()->count(2);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-using-another-fixture']);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);
    }

    public static function provideFixturesWhichLoadAnotherFixtureCases(): iterable
    {
        yield 'by fixture name' => ['fixture-using-another-fixture'];
        yield 'by group name' => ['fixture-using-another-fixture-group'];
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_a_story_and_reset_database(): void
    {
        if (TestKernel::usesDamaDoctrineTestBundle()) {
            self::markTestSkipped('test not applicable when using the DAMA: it somehow creates an infinite loop.');
        }

        GenericEntityFactory::createMany(5);

        $this->commandTester(['environment' => 'stories_as_fixtures'])->execute(['name' => 'fixture-story']);

        GenericEntityFactory::assert()->count(1);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);
    }

    /**
     * @test
     */
    #[Test]
    public function user_can_refuse_to_reset_database(): void
    {
        $commandTester = $this->commandTester(['environment' => 'stories_as_fixtures']);
        $commandTester->setInputs(['no']);
        $commandTester->execute(['name' => 'fixture-story']);

        self::assertStringContainsString('[WARNING] Aborting command execution', $commandTester->getDisplay());

        GenericEntityFactory::assert()->count(0);
    }

    /**
     * @test
     */
    #[Test]
    public function it_does_not_reset_database_if_append_option_is_used(): void
    {
        if (TestKernel::usesDamaDoctrineTestBundle()) {
            self::markTestSkipped('test not applicable when using the DAMA: it somehow creates an infinite loop.');
        }

        GenericEntityFactory::createMany(5);

        $this->commandTester(['environment' => 'stories_as_fixtures'])->execute(['name' => 'fixture-story', '--append' => true]);

        GenericEntityFactory::assert()->count(6);
    }

    /**
     * @test
     */
    #[Test]
    public function if_no_name_provided_it_asks_for_story_to_load(): void
    {
        $commandTester = $this->commandTester(['environment' => 'stories_as_fixtures']);

        $commandTester->setInputs([0]);
        $commandTester->execute(['--append' => true]);

        GenericEntityFactory::assert()->count(1);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);

        self::assertStringContainsString('Loading story with name "fixture-story"', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    #[Test]
    public function if_no_name_provided_it_asks_for_group_to_load(): void
    {
        $commandTester = $this->commandTester(['environment' => 'stories_as_fixtures']);

        $commandTester->setInputs([3, 1]); // ["chose a group to load", "multiple-fixtures-in-group"]
        $commandTester->execute(['--append' => true]);

        GenericEntityFactory::assert()->count(2);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story-for-group']);

        self::assertStringContainsString('Loading stories group "multiple-fixtures-in-group"', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    #[Test]
    public function if_no_name_provided_and_on_one_story_fixture_it_loads_it_automatically(): void
    {
        $commandTester = $this->commandTester(['environment' => 'stories_as_fixture_unique']);
        $commandTester->execute(['--append' => true]);

        GenericEntityFactory::assert()->count(1);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);

        self::assertStringContainsString('Loading story with name "fixture-story"', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    #[Test]
    public function it_does_not_load_global_state(): void
    {
        if (TestKernel::usesDamaDoctrineTestBundle()) {
            self::markTestSkipped('test not applicable when using the DAMA: it somehow creates an infinite loop.');
        }

        $this->commandTester(['environment' => 'story_fixture_and_global_state'])->execute([]);

        repository(GlobalEntity::class)->assert()->count(0);
    }

    private function commandTester(array $options = []): CommandTester
    {
        return new CommandTester((new Application(self::bootKernel($options)))->find('foundry:load-stories'));
    }
}
