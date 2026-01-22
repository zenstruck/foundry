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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyGenerator;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\EntityWithReadonly\EntityWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericProxyEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\proxy_factory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12
 */
#[IgnoreDeprecations]
#[RequiresPhpunit('>=12')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '0')]
#[RequiresEnvironmentVariable('DATABASE_URL')]
#[RequiresMethod(\Symfony\Component\VarExporter\LazyProxyTrait::class, 'createLazyProxy')]
final class DataProviderWithProxyPersistentEntityFactoryTest extends DataProviderWithPersistentFactoryTestCase
{
    #[Test]
    #[DataProvider('createOneProxyObjectInDataProvider')]
    public function assert_provided_data_is_proxy(?GenericModel $providedData): void
    {
        static::factory()::assert()->count(1);

        self::assertInstanceOf(Proxy::class, $providedData);
        self::assertNotInstanceOf(Proxy::class, ProxyGenerator::unwrap($providedData)); // asserts two proxies are not nested

        static::factory()::assert()->count(1);
        $providedData->_assertPersisted();
    }

    public static function createOneProxyObjectInDataProvider(): iterable
    {
        yield [
            static::factory()::createOne(),
        ];
    }

    #[Test]
    #[DataProvider('throwsExceptionWhenCreatingObjectInDataProvider')]
    #[RequiresPhp('<8.4')]
    public function it_throws_when_creating_persisted_object_with_non_proxy_factory_in_data_provider_without_php_84(?\Throwable $e): void
    {
        self::assertInstanceOf(\LogicException::class, $e);
        self::assertStringStartsWith(
            'Cannot create object in a data provider for non-proxy factories.',
            $e->getMessage()
        );
    }

    public static function throwsExceptionWhenCreatingObjectInDataProvider(): iterable
    {
        try {
            GenericEntityFactory::createOne();
        } catch (\Throwable $e) {
        }

        yield [$e ?? null];
    }

    protected static function factory(): GenericProxyEntityFactory
    {
        return GenericProxyEntityFactory::new();
    }

    /**
     * @return PersistentProxyObjectFactory<EntityWithReadonly>
     */
    protected static function objectWithReadonlyFactory(): PersistentObjectFactory
    {
        return proxy_factory(EntityWithReadonly::class, [
            'prop' => 1,
            'embedded' => new Embeddable('value1'),
            'date' => new \DateTimeImmutable(),
        ]);
    }
}
