<?php

namespace Zenstruck\Foundry\Tests\Unit\Bundle\DependencyInjection;

use Faker\Generator;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Zenstruck\Foundry\Bundle\DependencyInjection\ZenstruckFoundryExtension;
use Zenstruck\Foundry\Bundle\Maker\MakeFactory;
use Zenstruck\Foundry\Bundle\Maker\MakeStory;
use Zenstruck\Foundry\ChainManagerRegistry;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\ModelFactoryManager;
use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFoundryExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @test
     */
    public function default_config(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(Configuration::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(Configuration::class, 'setInstantiator', ['zenstruck_foundry.default_instantiator']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(Configuration::class, 'setFaker', ['zenstruck_foundry.faker']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(Configuration::class, 'setManagerRegistry', [ChainManagerRegistry::class]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(Configuration::class, 'setStoryManager', [StoryManager::class]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(Configuration::class, 'setModelFactoryManager', [ModelFactoryManager::class]);
        $this->assertCount(5, $this->container->findDefinition(Configuration::class)->getMethodCalls());
        $this->assertTrue($this->container->getDefinition(Configuration::class)->isPublic());
        $this->assertContainerBuilderHasService('zenstruck_foundry.default_instantiator', Instantiator::class);
        $this->assertEmpty($this->container->getDefinition('zenstruck_foundry.default_instantiator')->getMethodCalls());
        $this->assertContainerBuilderHasService('zenstruck_foundry.faker', Generator::class);
        $this->assertEmpty($this->container->getDefinition('zenstruck_foundry.faker')->getArguments());
        $this->assertContainerBuilderHasService(StoryManager::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(MakeFactory::class, 'maker.command');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(MakeStory::class, 'maker.command');
    }

    /**
     * @test
     */
    public function custom_faker_locale(): void
    {
        $this->load(['faker' => ['locale' => 'fr_FR']]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('zenstruck_foundry.faker', 0, 'fr_FR');
    }

    /**
     * @test
     */
    public function custom_faker_service(): void
    {
        $this->load(['faker' => ['service' => 'my_faker']]);

        $this->assertContainerBuilderHasService(Configuration::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(Configuration::class, 'setFaker', ['zenstruck_foundry.faker']);
        $this->assertContainerBuilderHasAlias('zenstruck_foundry.faker', 'my_faker');
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
    public function custom_instantiator_config(): void
    {
        $this->load(['instantiator' => [
            'without_constructor' => true,
            'allow_extra_attributes' => true,
            'always_force_properties' => true,
        ]]);

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('zenstruck_foundry.default_instantiator', 'withoutConstructor');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('zenstruck_foundry.default_instantiator', 'allowExtraAttributes');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('zenstruck_foundry.default_instantiator', 'alwaysForceProperties');
    }

    /**
     * @test
     */
    public function custom_instantiator_service(): void
    {
        $this->load(['instantiator' => ['service' => 'my_instantiator']]);

        $this->assertContainerBuilderHasService(Configuration::class);
        $this->assertContainerBuilderHasAlias('zenstruck_foundry.default_instantiator', 'my_instantiator');
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

        $this->assertContainerBuilderHasService(Configuration::class);
        $this->assertCount(6, $this->container->findDefinition(Configuration::class)->getMethodCalls());
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(Configuration::class, 'enableDefaultProxyAutoRefresh', []);
    }

    /**
     * @test
     */
    public function can_disable_auto_refresh_proxies(): void
    {
        $this->load(['auto_refresh_proxies' => false]);

        $this->assertContainerBuilderHasService(Configuration::class);
        $this->assertCount(6, $this->container->findDefinition(Configuration::class)->getMethodCalls());
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(Configuration::class, 'disableDefaultProxyAutoRefresh', []);
    }

    protected function getContainerExtensions(): array
    {
        return [new ZenstruckFoundryExtension()];
    }
}
