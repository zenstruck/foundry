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
use Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures\FixtureStoryWithSameName;
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
    public function it_throws_if_two_fixtures_have_the_same_name(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Cannot use #[AsFixture] name "fixture-story" for service "%s". This name is already used by service "%s".',
                FixtureStory::class,
                FixtureStoryWithSameName::class,
            )
        );

        $this->commandTester(['environment' => 'story_fixture_with_same_name'])->execute(['name' => 'fixture-story']);
    }

    private function commandTester(array $options = []): CommandTester
    {
        return new CommandTester((new Application(self::bootKernel($options)))->find('foundry:load-story'));
    }
}
