<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\InMemory;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\InMemory\InMemoryAddressRepository;
use Zenstruck\Foundry\Tests\Fixture\InMemory\InMemoryContactRepository;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[AsInMemoryTest]
#[ResetDatabase]
final class InMemoryUsingSetUpTest extends KernelTestCase
{
    use Factories;
    use RequiresORM;

    private InMemoryContactRepository $contactRepository;

    private Contact $contact;

    protected function setUp(): void
    {
        $this->contactRepository = self::getContainer()->get(InMemoryContactRepository::class); // @phpstan-ignore assign.propertyType

        $this->contact = ContactFactory::createOne();

        self::assertCount(1, ContactFactory::repository());
    }

    /**
     * @test
     */
    #[Test]
    public function can_access_objects_created_in_set_up_method(): void
    {
        self::assertCount(1, ContactFactory::repository());

        self::assertSame($this->contact, $this->contactRepository->_all()[0]);
    }
}
