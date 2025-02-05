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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Zenstruck\Foundry\Attribute\AsFoundryHook;
use Zenstruck\Foundry\Mongo\MongoResetter;
use Zenstruck\Foundry\Object\Event\Event;
use Zenstruck\Foundry\Object\Event\HookListenerFilter;
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
                ->arrayNode('orm')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('auto_persist')
                            ->info('Automatically persist entities when created.')
                            ->defaultTrue()
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
        $container->registerForAutoconfiguration(Factory::class)
            ->addTag('foundry.factory')
        ;

        $container->registerForAutoconfiguration(Story::class)
            ->addTag('foundry.story')
        ;

        $configurator->import('../config/services.php');

        $this->configureInstantiator($config['instantiator'], $container);
        $this->configureFaker($config['faker'], $container);
        $this->configureGlobalState($config['global_state'], $container);

        /** @var array<string, string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['MakerBundle'])) {
            $configurator->import('../config/makers.php');

            $makeFactoryDefinition = $container->getDefinition('.zenstruck_foundry.maker.factory');
            $makeFactoryDefinition->setArgument('$defaultNamespace', $config['make_factory']['default_namespace']);

            $makeStoryDefinition = $container->getDefinition('.zenstruck_foundry.maker.story');
            $makeStoryDefinition->setArgument('$defaultNamespace', $config['make_story']['default_namespace']);

            if (!isset($bundles['DoctrineBundle'])) {
                $container->removeDefinition('.zenstruck_foundry.maker.factory.orm_default_properties_guesser');
            }

            if (!isset($bundles['DoctrineMongoDBBundle'])) {
                $container->removeDefinition('.zenstruck_foundry.maker.factory.odm_default_properties_guesser');
            }

            if (!isset($bundles['DoctrineBundle']) && !isset($bundles['DoctrineMongoDBBundle'])) {
                $container->removeDefinition('.zenstruck_foundry.maker.factory.doctrine_scalar_fields_default_properties_guesser');
            }

            $container->getDefinition('.zenstruck_foundry.maker.factory.generator')
                ->setArgument('$forceProperties', $config['instantiator']['always_force_properties'] ?? false);
        } else {
            $configurator->import('../config/command_stubs.php');
        }

        if (isset($bundles['DoctrineBundle']) || isset($bundles['DoctrineMongoDBBundle'])) {
            $configurator->import('../config/persistence.php');
        }

        if (isset($bundles['DoctrineBundle'])) {
            $configurator->import('../config/orm.php');

            $container->getDefinition('.zenstruck_foundry.persistence_strategy.orm')
                ->replaceArgument(1, $config['orm'])
            ;

            $container->getDefinition('.zenstruck_foundry.persistence.database_resetter.orm.abstract')
                ->replaceArgument('$managers', $config['orm']['reset']['entity_managers'])
                ->replaceArgument('$connections', $config['orm']['reset']['connections'])
            ;

            /** @var ResetDatabaseMode $resetMode */
            $resetMode = $config['orm']['reset']['mode'];
            $container->getDefinition(OrmResetter::class)
                ->setClass(
                    match ($resetMode) {
                        ResetDatabaseMode::SCHEMA => SchemaDatabaseResetter::class,
                        ResetDatabaseMode::MIGRATE => MigrateDatabaseResetter::class,
                    }
                );

            if (ResetDatabaseMode::MIGRATE === $resetMode) {
                $container->getDefinition(OrmResetter::class)
                    ->replaceArgument('$configurations', $config['orm']['reset']['migrations']['configurations'])
                ;
            }
        }

        if (isset($bundles['DoctrineMongoDBBundle'])) {
            $configurator->import('../config/mongo.php');

            $container->getDefinition('.zenstruck_foundry.persistence_strategy.mongo')
                ->replaceArgument(1, $config['mongo'])
            ;

            $container->getDefinition(MongoResetter::class)
                ->replaceArgument(0, $config['mongo']['reset']['document_managers'])
            ;
        }

        $container->registerAttributeForAutoconfiguration(
            AsFoundryHook::class,
            // @phpstan-ignore argument.type
            static function(ChildDefinition $definition, AsFoundryHook $attribute, \ReflectionMethod $reflector) {
                if (1 !== \count($reflector->getParameters())
                    || !$reflector->getParameters()[0]->getType()
                    || !$reflector->getParameters()[0]->getType() instanceof \ReflectionNamedType
                    || !\is_a($reflector->getParameters()[0]->getType()->getName(), Event::class, true)
                ) {
                    throw new LogicException(\sprintf("In order to use \"%s\" attribute, method \"{$reflector->class}::{$reflector->name}()\" must have a single parameter that is a subclass of \"%s\".", AsFoundryHook::class, Event::class));
                }
                $definition->addTag('foundry.hook', [
                    'class' => $attribute->objectClass,
                    'method' => $reflector->getName(),
                    'event' => $reflector->getParameters()[0]->getType()->getName(),
                ]);
            }
        );
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass($this);
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

        // events
        $i = 0;
        foreach ($container->findTaggedServiceIds('foundry.hook') as $id => $tags) {
            foreach ($tags as $tag) {
                $container
                    ->setDefinition("foundry.hook.{$tag['event']}.{$i}", new Definition(class: HookListenerFilter::class))
                    ->setArgument(0, [new Reference($id), $tag['method']])
                    ->setArgument(1, $tag['class'])
                    ->addTag('kernel.event_listener', ['event' => $tag['event']])
                ;

                ++$i;
            }
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
        if ($config['service']) {
            $container->setAlias('.zenstruck_foundry.faker', $config['service']);

            return;
        }

        $definition = $container->getDefinition('.zenstruck_foundry.faker');

        if ($config['locale']) {
            $definition->addArgument($config['locale']);
        }

        $container->setParameter('zenstruck_foundry.faker.seed', $config['seed']);
    }
}
