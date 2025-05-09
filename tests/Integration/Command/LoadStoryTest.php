<?php

namespace Zenstruck\Foundry\Tests\Integration\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoadStoryTest extends KernelTestCase
{
    /**
     * @test
     */
    #[Test]
    public function it_can_load_a_story(): void
    {
        $application = new Application(self::createKernel());
        $command = $application->find('foundry:load-story');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'my-story']);
    }
}
