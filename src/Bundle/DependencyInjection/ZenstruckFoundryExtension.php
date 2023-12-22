<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Bundle\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\Foundry\Bundle\Command\StubMakeFactory;
use Zenstruck\Foundry\Bundle\Command\StubMakeStory;
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Test\ORMDatabaseResetter;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFoundryExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.xml');

        $container->registerForAutoconfiguration(Story::class)
            ->addTag('foundry.story')
        ;

        $container->registerForAutoconfiguration(PersistentProxyObjectFactory::class)
            ->addTag('foundry.factory')
        ;

        $this->configureFaker($mergedConfig['faker'], $container);
        $this->configureDefaultInstantiator($mergedConfig['instantiator'], $container);
        $this->configureDatabaseResetter($mergedConfig, $container);
        $this->configureMakeFactory($mergedConfig['make_factory'], $container, $loader);

        if (true === $mergedConfig['auto_refresh_proxies']) {
            $container->getDefinition('.zenstruck_foundry.configuration')->addMethodCall('enableDefaultProxyAutoRefresh');
        } elseif (false === $mergedConfig['auto_refresh_proxies']) {
            trigger_deprecation(
                'zenstruck\foundry',
                '1.37.0',
                <<<MESSAGE
                    Configuring the default proxy auto-refresh to false is deprecated. You should set it to "true", which will be the default value in 2.0.
                    If you still want to disable auto refresh, make your factory implement "%s" instead of "%s".
                    MESSAGE,
                PersistentObjectFactory::class,
                PersistentProxyObjectFactory::class,
            );

            $container->getDefinition('.zenstruck_foundry.configuration')->addMethodCall('disableDefaultProxyAutoRefresh');
        }
    }

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

        if ($config['seed']) {
            $definition->addMethodCall('seed', [$config['seed']]);
        }
    }

    private function configureDefaultInstantiator(array $config, ContainerBuilder $container): void
    {
        if ($config['service']) {
            $container->setAlias('.zenstruck_foundry.default_instantiator', $config['service']);

            return;
        }

        $definition = $container->getDefinition('.zenstruck_foundry.default_instantiator');

        if (isset($config['without_constructor'])
            && isset($config['use_constructor'])
            && $config['without_constructor'] === $config['use_constructor']
        ) {
            throw new \InvalidArgumentException('Cannot set "without_constructor" and "use_constructor" to the same value.');
        }

        $withoutConstructor = $config['without_constructor'] ?? !($config['use_constructor'] ?? true);

        $definition->setFactory([Instantiator::class, $withoutConstructor ? 'withoutConstructor' : 'withConstructor']);

        if ($config['allow_extra_attributes']) {
            $definition->addMethodCall('allowExtra');
        }

        if ($config['always_force_properties']) {
            $definition->addMethodCall('alwaysForce');
        }
    }

    private function configureDatabaseResetter(array $config, ContainerBuilder $container): void
    {
        $configurationDefinition = $container->getDefinition('.zenstruck_foundry.configuration');

        $legacyConfig = $config['database_resetter'];

        if (false === $legacyConfig['enabled']) {
            trigger_deprecation('zenstruck\foundry', '1.37.0', \sprintf('Disabling database reset via bundle configuration is deprecated and will be removed in 2.0. Instead you should not use "%s" trait in your test.', ResetDatabase::class));

            $configurationDefinition->addMethodCall('disableDatabaseReset');
        }

        if (isset($legacyConfig['orm']) && isset($config['orm']['reset'])) {
            throw new \InvalidArgumentException('Configurations "zenstruck_foundry.orm.reset" and "zenstruck_foundry.database_resetter.orm" are incompatible. You should only use "zenstruck_foundry.orm.reset".');
        }

        if ((isset($legacyConfig['orm']) || isset($config['orm']['reset'])) && !self::isBundleLoaded($container, DoctrineBundle::class)) {
            throw new \InvalidArgumentException('doctrine/doctrine-bundle should be enabled to use config under "orm.reset".');
        }

        if (isset($legacyConfig['odm']) && isset($config['mongo']['reset'])) {
            throw new \InvalidArgumentException('Configurations "zenstruck_foundry.mongo.reset" and "zenstruck_foundry.database_resetter.odm" are incompatible. You should only use "zenstruck_foundry.mongo.reset".');
        }

        if ((isset($legacyConfig['odm']) || isset($config['mongo']['reset'])) && !self::isBundleLoaded($container, DoctrineMongoDBBundle::class)) {
            throw new \InvalidArgumentException('doctrine/mongodb-odm-bundle should be enabled to use config under "mongo.reset".');
        }

        $configurationDefinition->setArgument('$ormConnectionsToReset', $legacyConfig['orm']['connections'] ?? $config['orm']['reset']['connections'] ?? ['default']);
        $configurationDefinition->setArgument('$ormObjectManagersToReset', $legacyConfig['orm']['object_managers'] ?? $config['orm']['reset']['entity_managers'] ?? ['default']);
        $configurationDefinition->setArgument('$ormResetMode', $legacyConfig['orm']['reset_mode'] ?? $config['orm']['reset']['mode'] ?? ORMDatabaseResetter::RESET_MODE_SCHEMA);
        $configurationDefinition->setArgument('$odmObjectManagersToReset', $legacyConfig['odm']['object_managers'] ?? $config['mongo']['reset']['document_managers'] ?? ['default']);
    }

    private function configureMakeFactory(array $makerConfig, ContainerBuilder $container, FileLoader $loader): void
    {
        if (\class_exists(AbstractMaker::class) && self::isBundleLoaded($container, MakerBundle::class)) {
            $loader->load('maker.xml');

            $makeFactoryDefinition = $container->getDefinition('.zenstruck_foundry.maker.factory');
            $makeFactoryDefinition->setArgument('$defaultNamespace', $makerConfig['default_namespace']);
        } else {
            $container->register('.zenstruck_foundry.maker.factory_stub', StubMakeFactory::class)->addTag('console.command');
            $container->register('.zenstruck_foundry.maker.story_stub', StubMakeStory::class)->addTag('console.command');
        }
    }

    private static function isBundleLoaded(ContainerBuilder $container, string $bundleName): bool
    {
        return \in_array(
            $bundleName,
            \is_array($bundles = $container->getParameter('kernel.bundles')) ? $bundles : [],
            true
        );
    }
}
