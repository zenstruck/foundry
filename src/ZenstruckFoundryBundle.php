<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\DependencyInjection\AsFixtureStoryCompilerPass;
use Zenstruck\Foundry\InMemory\DependencyInjection\InMemoryCompilerPass;
use Zenstruck\Foundry\InMemory\InMemoryRepository;
use Zenstruck\Foundry\Mongo\MongoResetter;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ORM\ResetDatabase\MigrateDatabaseResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode;
use Zenstruck\Foundry\ORM\ResetDatabase\SchemaDatabaseResetter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFoundryBundle extends AbstractBundle implements CompilerPassInterface
{
    public function boot(): void
    {
        if ($this->container && !Configuration::isBooted()) {
            Configuration::boot($this->container->get('.zenstruck_foundry.configuration')); // @phpstan-ignore argument.type
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode() // @phpstan-ignore method.notFound
            ->children()
                ->booleanNode('auto_refresh_proxies')
                    ->info('Whether to auto-refresh proxies by default (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#auto-refresh)')
                    ->defaultNull()
                    ->setDeprecated('zenstruck/foundry', '2.0', 'Since 2.0 auto_refresh_proxies defaults to true and this configuration has no effect.')
                ->end()
                ->booleanNode('enable_auto_refresh_with_lazy_objects')
                    ->info('Enable auto-refresh using PHP 8.4 lazy objects (cannot be enabled if PHP < 8.4).')
                    ->defaultNull()
                    ->validate()
                        ->ifTrue(fn(?bool $enableAutoRefreshWithLazyObjects): bool => $enableAutoRefreshWithLazyObjects && \PHP_VERSION_ID < 80400)
                        ->thenInvalid('Cannot enable auto-refresh with lazy objects if not using at least PHP 8.4.')
                    ->end()
                ->end()
                ->arrayNode('faker')
                    ->addDefaultsIfNotSet()
                    ->info('Configure the faker used by your factories.')
                    ->children()
                        ->scalarNode('locale')
                            ->info('The default locale to use for faker.')
                            ->example('fr_FR')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('seed')
                            ->setDeprecated('zenstruck/foundry', '2.4', 'The "faker.seed" configuration is deprecated and will be removed in 3.0. Use environment variable "FOUNDRY_FAKER_SEED" instead.')
                            ->info('Random number generator seed to produce the same fake values every run.')
                            ->example(1234)
                            ->defaultNull()
                        ->end()
                        ->scalarNode('service')
                            ->info('Service id for custom faker instance.')
                            ->example('my_faker')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('instantiator')
                    ->addDefaultsIfNotSet()
                    ->info('Configure the default instantiator used by your object factories.')
                    ->children()
                        ->booleanNode('use_constructor')
                            ->info('Use the constructor to instantiate objects.')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('allow_extra_attributes')
                            ->info('Whether or not to skip attributes that do not correspond to properties.')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('always_force_properties')
                            ->info('Whether or not to skip setters and force set object properties (public/private/protected) directly.')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('service')
                            ->info('Service id of your custom instantiator.')
                            ->example('my_instantiator')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('global_state')
                    ->info('Stories or invokable services to be loaded before each test.')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('persistence')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('flush_once')
                            ->info('Flush only once per call of `PersistentObjectFactory::create()` in userland.')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('orm')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('auto_persist')
                            ->info('Automatically persist entities when created.')
                            ->defaultTrue()
                            ->setDeprecated('zenstruck/foundry', '2.4', 'Since 2.4 auto_persist defaults to true and this configuration has no effect.')
                        ->end()
                        ->arrayNode('reset')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('connections')
                                    ->info('DBAL connections to reset with ResetDatabase trait')
                                    ->defaultValue(['default'])
                                    ->scalarPrototype()->end()
                                ->end()
                                ->arrayNode('entity_managers')
                                    ->info('Entity Managers to reset with ResetDatabase trait')
                                    ->defaultValue(['default'])
                                    ->scalarPrototype()->end()
                                ->end()
                                ->enumNode('mode')
                                    ->info('Reset mode to use with ResetDatabase trait')
                                    ->defaultValue(ResetDatabaseMode::SCHEMA)
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(static fn(string $mode): ?ResetDatabaseMode => ResetDatabaseMode::tryFrom($mode))
                                    ->end()
                                    ->values(ResetDatabaseMode::cases())
                                ->end()
                                ->arrayNode('migrations')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->arrayNode('configurations')
                                            ->info('Migration configurations')
                                            ->defaultValue([])
                                            ->scalarPrototype()->end()
                                            ->validate()
                                                ->ifTrue(function(array $configurationFiles): bool {
                                                    foreach ($configurationFiles as $configurationFile) {
                                                        if (!\is_file($configurationFile)) {
                                                            return true;
                                                        }
                                                    }

                                                    return false;
                                                })
                                                ->thenInvalid('At least one migrations configuration file does not exist.')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mongo')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('auto_persist')
                            ->info('Automatically persist documents when created.')
                            ->defaultTrue()
                            ->setDeprecated('zenstruck/foundry', '2.4', 'Since 2.4 auto_persist defaults to true and this configuration has no effect.')
                        ->end()
                        ->arrayNode('reset')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('document_managers')
                                    ->info('Document Managers to reset with ResetDatabase trait')
                                    ->defaultValue(['default'])
                                    ->scalarPrototype()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->end()
                ->arrayNode('make_factory')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_namespace')
                            ->info('Default namespace where factories will be created by maker.')
                            ->defaultValue('Factory')
                            ->cannotBeEmpty()
                        ->end()
                        ->booleanNode('add_hints')
                            ->info('Add "beginner" hints in the created factory.')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('make_story')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_namespace')
                            ->info('Default namespace where stories will be created by maker.')
                            ->defaultValue('Story')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void // @phpstan-ignore missingType.iterableValue
    {
        $container->registerForAutoconfiguration(Factory::class)->addTag('foundry.factory');

        $container->registerForAutoconfiguration(Story::class)->addTag('foundry.story');

        $configurator->import('../config/services.php');

        $this->configureInstantiator($config['instantiator'], $container);
        $this->configureFaker($config['faker'], $container);
        $this->configureGlobalState($config['global_state'], $container);
        $this->configureMakers($configurator, $container, $config);
        $this->configurePersistence($container, $configurator, $config);
        $this->configureInMemory($configurator, $container);
        $this->configureFixturesStory($container);
        $this->configureAutoRefreshWithLazyObjects($container, $config['enable_auto_refresh_with_lazy_objects'] ?? null);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass($this);
        $container->addCompilerPass(new InMemoryCompilerPass());
        $container->addCompilerPass(new AsFixtureStoryCompilerPass());
    }

    public function process(ContainerBuilder $container): void
    {
        // faker providers
        foreach ($container->findTaggedServiceIds('foundry.faker_provider') as $id => $tags) {
            $container
                ->getDefinition('.zenstruck_foundry.faker')
                ->addMethodCall('addProvider', [new Reference($id)])
            ;
        }
    }

    /**
     * @param string[] $values
     */
    private function configureGlobalState(array $values, ContainerBuilder $container): void
    {
        $values = \array_map(
            static fn(string $v) => \is_a($v, Story::class, true) ? $v : new Reference($v),
            $values
        );

        $container->getDefinition('.zenstruck_foundry.story_registry')
            ->replaceArgument(1, $values)
        ;
    }

    /**
     * @param mixed[] $config
     */
    private function configureInstantiator(array $config, ContainerBuilder $container): void
    {
        if ($config['service']) {
            $container->setAlias('.zenstruck_foundry.instantiator', $config['service']);

            return;
        }

        if (!$config['use_constructor']) {
            $container->getDefinition('.zenstruck_foundry.instantiator')
                ->setFactory([Instantiator::class, 'withoutConstructor'])
            ;
        }

        if ($config['allow_extra_attributes']) {
            $container->getDefinition('.zenstruck_foundry.instantiator')
                ->addMethodCall('allowExtra', returnsClone: true)
            ;
        }

        if ($config['always_force_properties']) {
            $container->getDefinition('.zenstruck_foundry.instantiator')
                ->addMethodCall('alwaysForce', returnsClone: true)
            ;
        }
    }

    /**
     * @param mixed[] $config
     */
    private function configureFaker(array $config, ContainerBuilder $container): void
    {
        $container->setParameter('zenstruck_foundry.faker.seed', $config['seed']);

        if ($config['service']) {
            $container->setAlias('.zenstruck_foundry.faker', $config['service']);

            return;
        }

        $definition = $container->getDefinition('.zenstruck_foundry.faker');

        if ($config['locale']) {
            $definition->addArgument($config['locale']);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function configureMakers(ContainerConfigurator $configurator, ContainerBuilder $container, array $config): void
    {
        /** @var array<string, string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['MakerBundle'])) {
            $configurator->import('../config/command_stubs.php');

            return;
        }

        $configurator->import('../config/makers.php');

        $makeFactoryDefinition = $container->getDefinition('.zenstruck_foundry.maker.factory');
        $makeFactoryDefinition->setArgument('$defaultNamespace', $config['make_factory']['default_namespace']);
        $makeFactoryDefinition->setArgument('$addHints', $config['make_factory']['add_hints']);

        $makeStoryDefinition = $container->getDefinition('.zenstruck_foundry.maker.story');
        $makeStoryDefinition->setArgument('$defaultNamespace', $config['make_story']['default_namespace']);

        if (!isset($bundles['DoctrineBundle'])) {
            $container->removeDefinition('.zenstruck_foundry.maker.factory.orm_default_properties_guesser');
        }

        if (!isset($bundles['DoctrineMongoDBBundle'])) {
            $container->removeDefinition('.zenstruck_foundry.maker.factory.odm_default_properties_guesser');
        }

        if (!isset($bundles['DoctrineBundle']) && !isset($bundles['DoctrineMongoDBBundle'])) {
            $container->removeDefinition(
                '.zenstruck_foundry.maker.factory.doctrine_scalar_fields_default_properties_guesser'
            );
        }

        $container->getDefinition('.zenstruck_foundry.maker.factory.generator')
            ->setArgument('$forceProperties', $config['instantiator']['always_force_properties'] ?? false);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function configurePersistence(ContainerBuilder $container, ContainerConfigurator $configurator, array $config): void
    {
        if (false === $config['persistence']['flush_once']) {
            trigger_deprecation('zenstruck/foundry', '2.5', 'Not setting "zenstruck_foundry.persistence.flush_once" to true is deprecated. This option will be forced to true in 3.0');
        }

        $container->setParameter('zenstruck_foundry.persistence.flush_once', $config['persistence']['flush_once']);

        /** @var array<string, string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['DoctrineBundle']) || isset($bundles['DoctrineMongoDBBundle'])) {
            $configurator->import('../config/persistence.php');
        } else {
            return;
        }

        if (isset($bundles['DoctrineBundle'])) {
            $this->configureOrm($configurator, $container, $config['orm']);
        }

        if (isset($bundles['DoctrineMongoDBBundle'])) {
            $this->configureMongo($configurator, $container, $config['mongo']);
        }
    }

    /**
     * @param array<string, mixed> $ormConfig
     */
    private function configureOrm(ContainerConfigurator $configurator, ContainerBuilder $container, array $ormConfig): void
    {
        $configurator->import('../config/orm.php');

        $container->getDefinition('.zenstruck_foundry.persistence.database_resetter.orm.abstract')
            ->replaceArgument('$managers', $ormConfig['reset']['entity_managers'])
            ->replaceArgument('$connections', $ormConfig['reset']['connections']);

        /** @var ResetDatabaseMode $resetMode */
        $resetMode = $ormConfig['reset']['mode'];
        $container->getDefinition(OrmResetter::class)
            ->setClass(
                match ($resetMode) {
                    ResetDatabaseMode::SCHEMA => SchemaDatabaseResetter::class,
                    ResetDatabaseMode::MIGRATE => MigrateDatabaseResetter::class,
                }
            );

        if (ResetDatabaseMode::MIGRATE === $resetMode) {
            $container->getDefinition(OrmResetter::class)
                ->replaceArgument('$configurations', $ormConfig['reset']['migrations']['configurations']);
        }
    }

    /**
     * @param array<string, mixed> $mongoConfig
     */
    private function configureMongo(ContainerConfigurator $configurator, ContainerBuilder $container, array $mongoConfig): void
    {
        $configurator->import('../config/mongo.php');

        $container->getDefinition(MongoResetter::class)
            ->replaceArgument(0, $mongoConfig['reset']['document_managers']);
    }

    private function configureInMemory(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/in_memory.php');
        $container->registerForAutoconfiguration(InMemoryRepository::class)->addTag('foundry.in_memory.repository');
    }

    private function configureFixturesStory(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsFixture::class,
            // @phpstan-ignore argument.type
            static function(ChildDefinition $definition, AsFixture $attribute, \ReflectionClass $reflector) {
                if (false === $reflector->getParentClass() || Story::class !== $reflector->getParentClass()->getName()) {
                    throw new LogicException(\sprintf('Only stories can be marked with "%s" attribute, class "%s" is not a story.', AsFixture::class, $reflector->getName()));
                }

                $definition->addTag('foundry.story.fixture', ['name' => $attribute->name, 'groups' => $attribute->groups]);
            }
        );
    }

    private function configureAutoRefreshWithLazyObjects(ContainerBuilder $container, ?bool $enableAutoRefreshWithLazyObjects): void
    {
        $container->setParameter('zenstruck_foundry.enable_auto_refresh_with_lazy_objects', $enableAutoRefreshWithLazyObjects ?? false);

        if (null === $enableAutoRefreshWithLazyObjects && \PHP_VERSION_ID >= 80400) {
            trigger_deprecation('zenstruck/foundry', '2.7', 'Not setting a value for "zenstruck_foundry.enable_auto_refresh_with_lazy_objects" is deprecated. This option will be forced to true in 3.0.');

            $container->removeDefinition('.foundry.persistence.objects_tracker');
        }
    }
}
