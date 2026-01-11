<?php

namespace Zenstruck\Foundry\Behat\Tests;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;

class FeatureContext implements Context
{
    public function __construct(
        private KernelInterface $kernel,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Given('A contact is created')]
    public function aContactIsCreated(): void
    {
        $contact = ContactFactory::createOne();

        // ensure the contact can be accessed by Behat's EntityManager instance
        $this->entityManager->refresh($contact);
    }

    #[Then('A contact should exist')]
    public function aContactShouldExist(): void
    {
        ContactFactory::assert()->count(1);
    }

    #[BeforeScenario]
    public function createDB(): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $this->runCommand($application, 'doctrine:schema:drop --force');
        $this->runCommand($application, 'doctrine:schema:create');
    }

    private function runCommand(Application $application, string $command, bool $canFail = false): void
    {
        $exit = $application->run(new StringInput("{$command} --no-interaction"), $output = new BufferedOutput());

        if (0 !== $exit && !$canFail) {
            throw new \RuntimeException(\sprintf('Error running "%s": %s', $command, $output->fetch()));
        }
    }
}
