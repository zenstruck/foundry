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

namespace Zenstruck\Foundry\Tests\Integration\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyGenerator;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ProxyContactFactory;
use Zenstruck\Foundry\Tests\Fixture\InMemory\InMemoryContactRepository;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class DataProviderWithInMemoryTest extends KernelTestCase
{
    use Factories;
    use RequiresORM; // needed to use the entity manager
    use ResetDatabase;

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
    #[IgnoreDeprecations]
    public function it_can_create_in_memory_factory_in_data_provider(PersistentObjectFactory $factory): void
    {
        if ('1' !== ($_ENV['USE_FOUNDRY_PHPUNIT_EXTENSION'] ?? null)) {
            self::markTestSkipped('Needs Foundry PHPUnit extension.');
        }

        $contact = $factory->create();

        if (TestKernel::canUseLegacyProxy()) {
            self::assertSame([ProxyGenerator::unwrap($contact)], $this->contactRepository->_all());
        } else {
            self::assertSame([$contact], $this->contactRepository->_all());
        }

        self::assertSame(0, $this->entityManager->getRepository(Contact::class)->count([]));
    }

    public static function provideContactFactory(): iterable
    {
        yield [ContactFactory::new()];

        if (TestKernel::canUseLegacyProxy()) {
            yield [ProxyContactFactory::new()]; // @phpstan-ignore argument.type
        }
    }

    #[Test]
    #[DataProvider('provideContact')]
    #[AsInMemoryTest]
    #[RequiresPhp('^8.4')]
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

    #[Test]
    #[DataProvider('provideContactWithLegacyProxy')]
    #[AsInMemoryTest]
    #[RequiresMethod(\Symfony\Component\VarExporter\LazyProxyTrait::class, 'createLazyProxy')]
    #[IgnoreDeprecations('(p|P)roxy')]
    public function it_can_create_in_memory_objects_in_data_provider_with_legacy_proxy(?Contact $contact = null): void
    {
        self::assertInstanceOf(Contact::class, $contact);

        self::assertSame([ProxyGenerator::unwrap($contact)], $this->contactRepository->_all());

        self::assertSame(0, $this->entityManager->getRepository(Contact::class)->count());
    }

    public static function provideContactWithLegacyProxy(): iterable
    {
        yield [ProxyContactFactory::createOne()];
    }
}
