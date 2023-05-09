<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Bundle\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Faker\Generator;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Zenstruck\Foundry\Bundle\DependencyInjection\ZenstruckFoundryExtension;
use Zenstruck\Foundry\Instantiator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFoundryExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setParameter('kernel.bundles', []);
    }

    /**
     * @test
     */
    public function default_config(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService('.zenstruck_foundry.configuration');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.configuration', 'setInstantiator', ['.zenstruck_foundry.default_instantiator']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.configuration', 'setFaker', ['.zenstruck_foundry.faker']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.configuration', 'setManagerRegistry', ['.zenstruck_foundry.chain_manager_registry']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.configuration', 'setStoryManager', ['.zenstruck_foundry.story_manager']);
        $this->assertCount(4, $this->container->findDefinition('.zenstruck_foundry.configuration')->getMethodCalls());
        $this->assertTrue($this->container->getDefinition('.zenstruck_foundry.configuration')->isPublic());
        $this->assertContainerBuilderHasService('.zenstruck_foundry.default_instantiator', Instantiator::class);
        $this->assertEmpty($this->container->getDefinition('.zenstruck_foundry.default_instantiator')->getMethodCalls());
        $this->assertContainerBuilderHasService('.zenstruck_foundry.faker', Generator::class);
        $this->assertEmpty($this->container->getDefinition('.zenstruck_foundry.faker')->getArguments());
        $this->assertContainerBuilderHasService('.zenstruck_foundry.story_manager');

        $this->assertContainerBuilderHasService('.zenstruck_foundry.factory_manager');
        $this->assertContainerBuilderHasService('.zenstruck_foundry.persistence_manager');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.persistence_manager', 'setManagerRegistry', ['.zenstruck_foundry.chain_manager_registry']);

        $this->assertContainerBuilderNotHasService('.zenstruck_foundry.maker.factory');
        $this->assertContainerBuilderNotHasService('.zenstruck_foundry.maker.story');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('.zenstruck_foundry.maker.factory_stub', 'console.command');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('.zenstruck_foundry.maker.story_stub', 'console.command');
    }

    /**
     * @test
     */
    public function custom_faker_locale(): void
    {
        $this->load(['faker' => ['locale' => 'fr_FR']]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('.zenstruck_foundry.faker', 0, 'fr_FR');
    }

    /**
     * @test
     */
    public function custom_faker_seed(): void
    {
        $this->load(['faker' => ['seed' => 1234]]);

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.faker', 'seed', [1234]);
    }

    /**
     * @test
     */
    public function custom_faker_service(): void
    {
        $this->load(['faker' => ['service' => 'my_faker']]);

        $this->assertContainerBuilderHasService('.zenstruck_foundry.configuration');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.configuration', 'setFaker', ['.zenstruck_foundry.faker']);
        $this->assertContainerBuilderHasAlias('.zenstruck_foundry.faker', 'my_faker');
    }

    /**
     * @test
     */
    public function cannot_set_faker_locale_and_service(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_foundry.faker": Cannot set faker locale when using custom service.');

        $this->load(['faker' => ['service' => 'my_faker', 'locale' => 'fr_FR']]);
    }

    /**
     * @test
     */
    public function cannot_set_faker_seed_and_service(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_foundry.faker": Cannot set faker seed when using custom service.');

        $this->load(['faker' => ['service' => 'my_faker', 'seed' => 1234]]);
    }

    /**
     * @test
     */
    public function custom_instantiator_config(): void
    {
        $this->load(['instantiator' => [
            'without_constructor' => true,
            'allow_extra_attributes' => true,
            'always_force_properties' => true,
        ]]);

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.default_instantiator', 'withoutConstructor');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.default_instantiator', 'allowExtraAttributes');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.default_instantiator', 'alwaysForceProperties');
    }

    /**
     * @test
     */
    public function custom_instantiator_service(): void
    {
        $this->load(['instantiator' => ['service' => 'my_instantiator']]);

        $this->assertContainerBuilderHasService('.zenstruck_foundry.configuration');
        $this->assertContainerBuilderHasAlias('.zenstruck_foundry.default_instantiator', 'my_instantiator');
    }

    /**
     * @test
     */
    public function cannot_configure_allow_extra_attributes_if_using_custom_instantiator_service(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_foundry.instantiator": Cannot set "allow_extra_attributes" when using custom service.');

        $this->load(['instantiator' => ['service' => 'my_instantiator', 'allow_extra_attributes' => true]]);
    }

    /**
     * @test
     */
    public function cannot_configure_without_constructor_if_using_custom_instantiator_service(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_foundry.instantiator": Cannot set "without_constructor" when using custom service.');

        $this->load(['instantiator' => ['service' => 'my_instantiator', 'without_constructor' => true]]);
    }

    /**
     * @test
     */
    public function cannot_configure_always_force_properties_if_using_custom_instantiator_service(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_foundry.instantiator": Cannot set "always_force_properties" when using custom service.');

        $this->load(['instantiator' => ['service' => 'my_instantiator', 'always_force_properties' => true]]);
    }

    /**
     * @test
     */
    public function can_enable_auto_refresh_proxies(): void
    {
        $this->load(['auto_refresh_proxies' => true]);

        $this->assertContainerBuilderHasService('.zenstruck_foundry.configuration');
        $this->assertCount(5, $this->container->findDefinition('.zenstruck_foundry.configuration')->getMethodCalls());
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.configuration', 'enableDefaultProxyAutoRefresh', []);
    }

    /**
     * @test
     */
    public function can_disable_auto_refresh_proxies(): void
    {
        $this->load(['auto_refresh_proxies' => false]);

        $this->assertContainerBuilderHasService('.zenstruck_foundry.configuration');
        $this->assertCount(5, $this->container->findDefinition('.zenstruck_foundry.configuration')->getMethodCalls());
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('.zenstruck_foundry.configuration', 'disableDefaultProxyAutoRefresh', []);
    }

    /**
     * @test
     */
    public function it_registers_makers_if_maker_bundle_enabled(): void
    {
        $this->setParameter('kernel.bundles', [MakerBundle::class]);
        $this->load();

        $this->assertContainerBuilderHasServiceDefinitionWithTag('.zenstruck_foundry.maker.factory', 'maker.command');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('.zenstruck_foundry.maker.story', 'maker.command');
    }

    /**
     * @test
     * @testWith ["orm"]
     *           ["odm"]
     */
    public function cannot_configure_database_resetter_if_doctrine_not_enabled(string $doctrine): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('should be enabled to use config under "database_resetter.%s"', $doctrine));

        $this->load(['database_resetter' => [$doctrine => []]]);
    }

    /**
     * @test
     */
    public function can_configure_database_resetter_if_doctrine_enabled(): void
    {
        $this->setParameter('kernel.bundles', [DoctrineBundle::class, DoctrineMongoDBBundle::class]);

        $this->load([
            'database_resetter' => [
                'orm' => ['connections' => ['orm_connection'], 'object_managers' => ['object_manager_orm'], 'reset_mode' => 'migrate'],
                'odm' => ['object_managers' => ['object_manager_odm']],
            ],
        ]);

        $configurationArguments = $this->container->getDefinition('.zenstruck_foundry.configuration')->getArguments();
        $this->assertSame(['orm_connection'], $configurationArguments['$ormConnectionsToReset']);
        $this->assertSame(['object_manager_orm'], $configurationArguments['$ormObjectManagersToReset']);
        $this->assertSame('migrate', $configurationArguments['$ormResetMode']);
        $this->assertSame(['object_manager_odm'], $configurationArguments['$odmObjectManagersToReset']);
    }

    /**
     * @return ZenstruckFoundryExtension[]
     */
    protected function getContainerExtensions(): array
    {
        return [new ZenstruckFoundryExtension()];
    }
}
