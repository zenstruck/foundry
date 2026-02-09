<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\InMemory\InMemoryContactRepository;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[RequiresEnvironmentVariable('DATABASE_URL')] // needed to use the entity manager
#[ResetDatabase]
final class DataProviderWithInMemoryTest extends KernelTestCase
{
    private InMemoryContactRepository $contactRepository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->contactRepository = self::getContainer()->get(InMemoryContactRepository::class); // @phpstan-ignore assign.propertyType

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class); // @phpstan-ignore assign.propertyType
    }

    /**
     * @param PersistentObjectFactory<Contact> $factory
     */
    #[Test]
    #[DataProvider('provideContactFactory')]
    #[AsInMemoryTest]
    public function it_can_create_in_memory_factory_in_data_provider(PersistentObjectFactory $factory): void
    {
        $contact = $factory->create();

        self::assertSame([$contact], $this->contactRepository->_all());

        self::assertSame(0, $this->entityManager->getRepository(Contact::class)->count([]));
    }

    public static function provideContactFactory(): iterable
    {
        yield [ContactFactory::new()];
    }

    #[Test]
    #[DataProvider('provideContact')]
    #[AsInMemoryTest]
    public function it_can_create_in_memory_objects_in_data_provider(?Contact $contact = null): void
    {
        self::assertInstanceOf(Contact::class, $contact);

        self::assertSame([$contact], $this->contactRepository->_all());

        self::assertSame(0, $this->entityManager->getRepository(Contact::class)->count());
    }

    public static function provideContact(): iterable
    {
        yield [ContactFactory::createOne()];
    }
}
