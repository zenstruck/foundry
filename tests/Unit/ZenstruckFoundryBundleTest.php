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

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Loader\DefinitionFileLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode;
use Zenstruck\Foundry\Tests\Fixture\ExtendedGenerator;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @group legacy
 */
#[IgnoreDeprecations] // default configuration uses flush_once: false, which is deprecated
final class ZenstruckFoundryBundleTest extends TestCase
{
    private ZenstruckFoundryBundle $bundle;
    private ContainerBuilder $container;
    private ContainerConfigurator $configurator;

    protected function setUp(): void
    {
        if (!\class_exists(FrameworkBundle::class)) {
            self::markTestSkipped('symfony/framework-bundle needed.');
        }

        $this->container = new ContainerBuilder(new ParameterBag([
            'kernel.bundles' => [],
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.root_dir' => \sys_get_temp_dir(),
            'kernel.project_dir' => \sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.debug' => true,
            'kernel.container_class' => Container::class,
        ]));

        $this->bundle = new ZenstruckFoundryBundle();

        $instanceof = [];
        $fileLoader = new PhpFileLoader($this->container, new FileLocator(__DIR__));

        $this->configurator = new ContainerConfigurator($this->container, $fileLoader, $instanceof, __DIR__, '');
    }

    /**
     * @test
     */
    #[Test]
    public function faker_seed_default_value(): void
    {
        $config = self::buildConfiguration();

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertTrue($this->container->hasParameter('zenstruck_foundry.faker.seed'));
        self::assertNull($this->container->getParameter('zenstruck_foundry.faker.seed'));
    }

    /**
     * @test
     *
     * @group legacy
     */
    #[Test]
    #[IgnoreDeprecations]
    public function faker_seed_value_overridden(): void
    {
        $config = self::buildConfiguration([['faker' => ['seed' => $expected = 1234]]]);

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertTrue($this->container->hasParameter('zenstruck_foundry.faker.seed'));
        self::assertSame($expected, $this->container->getParameter('zenstruck_foundry.faker.seed'));
    }

    /**
     * @test
     */
    #[Test]
    public function container_has_default_faker_service_definition(): void
    {
        $this->bundle->loadExtension(self::buildConfiguration(), $this->configurator, $this->container);

        self::assertTrue($this->container->hasDefinition('.zenstruck_foundry.faker'));
    }

    /**
     * @test
     */
    #[Test]
    public function default_faker_service_can_receive_a_locale_via_configuration(): void
    {
        $config = self::buildConfiguration([['faker' => ['locale' => $expected = 'en_US']]]);

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertTrue($this->container->hasParameter('zenstruck_foundry.faker.seed'));
        self::assertTrue($this->container->hasDefinition('.zenstruck_foundry.faker'));

        $definition = $this->container->getDefinition('.zenstruck_foundry.faker');
        self::assertSame($expected, $definition->getArgument(0));
    }

    /**
     * @test
     */
    #[Test]
    public function faker_service_can_be_overridden_with_configuration(): void
    {
        $config = self::buildConfiguration([['faker' => ['service' => $expected = ExtendedGenerator::class]]]);
        $this->container->setDefinition($expected, new Definition($expected));

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertTrue($this->container->hasAlias('.zenstruck_foundry.faker'));
        self::assertSame($expected, $this->container->get('.zenstruck_foundry.faker')::class);
        self::assertTrue($this->container->hasParameter('zenstruck_foundry.faker.seed'));
    }

    /**
     * @test
     */
    #[Test]
    public function container_has_default_instanciator(): void
    {
        $this->bundle->loadExtension(self::buildConfiguration(), $this->configurator, $this->container);

        self::assertTrue($this->container->hasDefinition('.zenstruck_foundry.instantiator'));
        self::assertSame([Instantiator::class, 'withConstructor'], $this->container->getDefinition('.zenstruck_foundry.instantiator')->getFactory());
        self::assertEmpty($this->container->getDefinition('.zenstruck_foundry.instantiator')->getMethodCalls());
    }

    /**
     * @test
     */
    #[Test]
    public function service_can_be_overridden_with_configuration(): void
    {
        $config = self::buildConfiguration([['instantiator' => ['service' => $expected = ExtendedGenerator::class]]]);
        $this->container->setDefinition($expected, new Definition($expected));

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertTrue($this->container->hasAlias('.zenstruck_foundry.instantiator'));
        self::assertSame($expected, $this->container->get('.zenstruck_foundry.instantiator')::class);
    }

    /**
     * @test
     */
    #[Test]
    public function create_instantiator_without_constructor_configuration(): void
    {
        $config = self::buildConfiguration([['instantiator' => ['use_constructor' => false]]]);

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertTrue($this->container->hasDefinition('.zenstruck_foundry.instantiator'));

        self::assertSame([Instantiator::class, 'withoutConstructor'], $this->container->getDefinition('.zenstruck_foundry.instantiator')->getFactory());
        self::assertSame([], $this->container->getDefinition('.zenstruck_foundry.instantiator')->getMethodCalls());
    }

    /**
     * @test
     */
    #[Test]
    public function create_instantiator_without_constructor_and_with_extra_configuration(): void
    {
        $config = self::buildConfiguration([['instantiator' => ['use_constructor' => false, 'allow_extra_attributes' => true]]]);

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertTrue($this->container->hasDefinition('.zenstruck_foundry.instantiator'));

        self::assertSame([Instantiator::class, 'withoutConstructor'], $this->container->getDefinition('.zenstruck_foundry.instantiator')->getFactory());
        self::assertSame([['allowExtra', [], true]], $this->container->getDefinition('.zenstruck_foundry.instantiator')->getMethodCalls());
    }

    /**
     * @test
     */
    #[Test]
    public function create_instantiator_without_constructor_and_with_extra_and_with_forced_properties_configuration(): void
    {
        $config = self::buildConfiguration([['instantiator' => ['use_constructor' => false, 'allow_extra_attributes' => true, 'always_force_properties' => true]]]);

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertTrue($this->container->hasDefinition('.zenstruck_foundry.instantiator'));

        self::assertSame([Instantiator::class, 'withoutConstructor'], $this->container->getDefinition('.zenstruck_foundry.instantiator')->getFactory());
        self::assertSame([['allowExtra', [], true], ['alwaysForce', [], true]], $this->container->getDefinition('.zenstruck_foundry.instantiator')->getMethodCalls());
    }

    /**
     * @test
     * @requires PHP < 8.4
     */
    #[Test]
    #[RequiresPhp('<8.4')]
    public function cannot_enable_auto_refresh_with_lazy_objects_if_not_php84(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Cannot enable auto-refresh with lazy objects if not using at least PHP 8.4');

        $config = self::buildConfiguration([['enable_auto_refresh_with_lazy_objects' => true]]);

        $this->bundle->loadExtension($config, $this->configurator, $this->container);
    }

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>=8.4')]
    public function can_enable_auto_refresh_with_lazy_objects_if_at_leat_php84(): void
    {
        $config = self::buildConfiguration([['enable_auto_refresh_with_lazy_objects' => true]]);

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertTrue($config['enable_auto_refresh_with_lazy_objects']);
    }

    /**
     * @test
     * @requires PHP >= 8.4
     */
    #[Test]
    #[RequiresPhp('>=8.4')]
    public function can_disable_auto_refresh_with_lazy_objects_if_at_leat_php84(): void
    {
        $config = self::buildConfiguration([['enable_auto_refresh_with_lazy_objects' => false]]);

        $this->bundle->loadExtension($config, $this->configurator, $this->container);

        self::assertFalse($config['enable_auto_refresh_with_lazy_objects']);
    }

    /**
     * @test
     */
    #[Test]
    public function configuration_default_values(): void
    {
        self::assertSame([
            'auto_refresh_proxies' => null,
            'enable_auto_refresh_with_lazy_objects' => null,
            'faker' => [
                'locale' => null,
                'seed' => null,
                'manage_seed' => true,
                'service' => null,
            ],
            'instantiator' => [
                'use_constructor' => true,
                'allow_extra_attributes' => false,
                'always_force_properties' => false,
                'service' => null,
            ],
            'global_state' => [],
            'persistence' => ['flush_once' => false],
            'orm' => [
                'auto_persist' => true,
                'reset' => [
                    'connections' => ['default'],
                    'entity_managers' => ['default'],
                    'mode' => ResetDatabaseMode::SCHEMA,
                    'migrations' => [
                        'configurations' => [],
                    ],
                ],
            ],
            'mongo' => [
                'auto_persist' => true,
                'reset' => [
                    'document_managers' => ['default'],
                ],
            ],
            'make_factory' => [
                'default_namespace' => 'Factory',
                'add_hints' => true,
            ],
            'make_story' => [
                'default_namespace' => 'Story',
            ],
        ], self::buildConfiguration());
    }

    private static function buildConfiguration(array $config = []): array
    {
        $treeBuilder = new TreeBuilder('zenstruck_foundry');
        $definitionLoader = new DefinitionFileLoader($treeBuilder, new FileLocator());
        $configurator = new DefinitionConfigurator($treeBuilder, $definitionLoader, __DIR__, '');

        (new ZenstruckFoundryBundle())->configure($configurator);

        return (new Processor())->process($treeBuilder->buildTree(), $config);
    }
}
