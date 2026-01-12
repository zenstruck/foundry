<?php

namespace Zenstruck\Foundry\Tests\Fixture\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use function Zenstruck\Foundry\application;
use function Zenstruck\Foundry\runCommand;

final class TestContext implements Context
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EntityManagerInterface $entityManager,
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
    public function createDatabase(): void
    {
        $application = application($this->kernel);

        runCommand($application, 'doctrine:schema:drop --force');
        runCommand($application, 'doctrine:schema:create');
    }
}
