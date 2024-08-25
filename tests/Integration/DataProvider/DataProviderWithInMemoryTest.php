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
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\StandardContact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ProxyContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\StandardContactFactory;
use Zenstruck\Foundry\Tests\Fixture\InMemory\InMemoryStandardContactRepository;

use Zenstruck\Foundry\Tests\Integration\RequiresORM;
use function Zenstruck\Foundry\Persistence\unproxy;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit 11.4
 */
#[RequiresPhpunit('11.4')]
final class DataProviderWithInMemoryTest extends KernelTestCase
{
    use Factories;
    use RequiresORM; // needed to use the entity manager
    use ResetDatabase;

    private InMemoryStandardContactRepository $contactRepository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->contactRepository = self::getContainer()->get(InMemoryStandardContactRepository::class); // @phpstan-ignore assign.propertyType

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class); // @phpstan-ignore assign.propertyType
    }

    /**
     * @param PersistentObjectFactory<StandardContact> $factory
     */
    #[Test]
    #[DataProvider('provideContactFactory')]
    #[AsInMemoryTest]
    public function it_can_create_in_memory_factory_in_data_provider(PersistentObjectFactory $factory): void
    {
        if ('1' !== ($_ENV['USE_FOUNDRY_PHPUNIT_EXTENSION'] ?? null)) {
            self::markTestSkipped('Needs Foundry PHPUnit extension.');
        }

        $contact = $factory->create();

        self::assertSame([unproxy($contact)], $this->contactRepository->_all());

        self::assertSame(0, $this->entityManager->getRepository(StandardContact::class)->count());
    }

    public static function provideContactFactory(): iterable
    {
        yield [StandardContactFactory::new()];
        yield [ProxyContactFactory::new()];
    }

    #[Test]
    #[DataProvider('provideContact')]
    #[AsInMemoryTest]
    public function it_can_create_in_memory_objects_in_data_provider(?StandardContact $contact = null): void
    {
        if (\FOUNDRY_SKIP_DATA_PROVIDER === $this->dataName()) {
            $this->markTestSkipped();
        }

        self::assertInstanceOf(StandardContact::class, $contact);

        self::assertSame([unproxy($contact)], $this->contactRepository->_all());

        self::assertSame(0, $this->entityManager->getRepository(StandardContact::class)->count());
    }

    public static function provideContact(): iterable
    {
        if ('1' !== ($_ENV['USE_FOUNDRY_PHPUNIT_EXTENSION'] ?? null)) {
            yield \FOUNDRY_SKIP_DATA_PROVIDER => [null];

            return;
        }

        yield [ProxyContactFactory::createOne()];
    }
}
