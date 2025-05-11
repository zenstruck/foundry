<?php

namespace Zenstruck\Foundry\Tests\Integration\Command;

use DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures\FixtureStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures\FixtureStoryWithNameCollision;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

final class LoadStoryTest extends KernelTestCase
{
    use RequiresORM, ResetDatabase;
    use Factories; // todo: remove this?

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_story_does_not_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->commandTester()->execute(['name' => 'invalid-name', '--append' => true]);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_a_story(): void
    {
        $this->commandTester()->execute(['name' => 'fixture-story', '--append' => true]);

        GenericEntityFactory::assert()->count(1);
    }

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_name_collision_between_two_stories_name(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Cannot use #[AsFixture] name "fixture-story" for service "%s". This name is already used by service "%s".',
                FixtureStory::class,
                FixtureStoryWithNameCollision::class,
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
        $this->expectExceptionMessage('Cannot use #[AsFixture] group(s) "fixture-story" They collide with fixture names.');

        $this->commandTester(['environment' => 'story_fixture_with_group_name_collision'])->execute(['name' => 'fixture-story', '--append' => true]);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_one_single_story_based_on_its_group_name(): void
    {
        $this->commandTester()->execute(['name' => 'single-fixture-in-group', '--append' => true]);

        GenericEntityFactory::assert()->count(1);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_multiple_stories_based_on_their_group_name(): void
    {
        $this->commandTester()->execute(['name' => 'multiple-fixtures-in-group', '--append' => true]);

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
        $this->commandTester()->execute(['name' => $name, '--append' => true]);

        GenericEntityFactory::assert()->count(2);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-using-another-fixture']);
        GenericEntityFactory::assert()->count(1, ['prop1' => 'fixture-story']);
    }

    public static function provideFixturesWhichLoadAnotherFixtureCases(): iterable
    {
        yield 'by fixture name' =>  ['fixture-using-another-fixture'];
        yield 'by group name' =>  ['fixture-using-another-fixture-group'];
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

        $this->commandTester()->execute(['name' => 'fixture-story']);

        GenericEntityFactory::assert()->count(1);
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

        $this->commandTester()->execute(['name' => 'fixture-story', '--append' => true]);

        GenericEntityFactory::assert()->count(6);
    }

    private function commandTester(array $options = []): CommandTester
    {
        return new CommandTester((new Application(self::bootKernel($options)))->find('foundry:load-story'));
    }
}
