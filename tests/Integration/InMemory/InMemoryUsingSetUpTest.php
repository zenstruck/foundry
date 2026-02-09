<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\InMemory;

use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\InMemory\InMemoryContactRepository;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[AsInMemoryTest]
#[ResetDatabase]
#[RequiresEnvironmentVariable('DATABASE_URL')]
final class InMemoryUsingSetUpTest extends KernelTestCase
{
    private InMemoryContactRepository $contactRepository;

    private Contact $contact;

    protected function setUp(): void
    {
        $this->contactRepository = self::getContainer()->get(InMemoryContactRepository::class); // @phpstan-ignore assign.propertyType

        $this->contact = ContactFactory::createOne();

        self::assertCount(1, ContactFactory::repository());
    }

    #[Test]
    public function can_access_objects_created_in_set_up_method(): void
    {
        self::assertCount(1, ContactFactory::repository());

        self::assertSame($this->contact, $this->contactRepository->_all()[0]);
    }
}
