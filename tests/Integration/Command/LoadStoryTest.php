<?php

namespace Zenstruck\Foundry\Tests\Integration\Command;

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

        $this->commandTester()->execute(['name' => 'invalid-name']);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_a_story(): void
    {
        $this->commandTester()->execute(['name' => 'fixture-story']);

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

        $this->commandTester(['environment' => 'story_fixture_with_name_collision'])->execute(['name' => 'fixture-story']);
    }

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_name_collision_between_story_name_and_group_name(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot use #[AsFixture] group(s) "fixture-story" They collide with fixture names.');

        $this->commandTester(['environment' => 'story_fixture_with_group_name_collision'])->execute(['name' => 'fixture-story']);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_one_single_story_based_its_group_name(): void
    {
        $this->commandTester()->execute(['name' => 'single-fixture-in-group']);

        GenericEntityFactory::assert()->count(1);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_multiple_stories_based_their_group_name(): void
    {
        $this->commandTester()->execute(['name' => 'multiple-fixtures-in-group']);

        GenericEntityFactory::assert()->count(2);
    }

    private function commandTester(array $options = []): CommandTester
    {
        return new CommandTester((new Application(self::bootKernel($options)))->find('foundry:load-story'));
    }
}
