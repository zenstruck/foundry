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
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

final class LoadStoryTest extends KernelTestCase
{
    use RequiresORM, ResetDatabase;
    use Factories; // todo: remove this?

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $application = new Application(self::createKernel());
        $command = $application->find('foundry:load-story');

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_story_does_not_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->commandTester->execute(['name' => 'invalid-name']);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_load_a_story(): void
    {
        $this->commandTester->execute(['name' => 'fixture-story']);

        GenericEntityFactory::assert()->count(1);
    }
}
