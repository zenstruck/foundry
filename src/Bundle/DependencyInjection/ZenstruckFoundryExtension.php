<?php

namespace Zenstruck\Foundry\Bundle\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\Foundry\Bundle\Command\StubMakeFactory;
use Zenstruck\Foundry\Bundle\Command\StubMakeStory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Test\Behat\FactoriesContext;
use Zenstruck\Foundry\Test\Behat\ResetDatabaseContext;
use Zenstruck\Foundry\Test\ORMDatabaseResetter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFoundryExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.xml');
        $loader->load('maker.xml');

        $container->registerForAutoconfiguration(Story::class)
            ->addTag('foundry.story')
        ;

        $container->registerForAutoconfiguration(ModelFactory::class)
            ->addTag('foundry.factory')
        ;

        $this->configureFaker($mergedConfig['faker'], $container);
        $this->configureDefaultInstantiator($mergedConfig['instantiator'], $container);
        $this->configureDatabaseResetter($mergedConfig['database_resetter'], $container);

        if (true === $mergedConfig['auto_refresh_proxies']) {
            $container->getDefinition('.zenstruck_foundry.configuration')->addMethodCall('enableDefaultProxyAutoRefresh');
        } elseif (false === $mergedConfig['auto_refresh_proxies']) {
            $container->getDefinition('.zenstruck_foundry.configuration')->addMethodCall('disableDefaultProxyAutoRefresh');
        }

        if (!\class_exists(AbstractMaker::class)) {
            $container->register('.zenstruck_foundry.maker.factory_stub', StubMakeFactory::class)->addTag('console.command');
            $container->register('.zenstruck_foundry.maker.story_stub', StubMakeStory::class)->addTag('console.command');
        }

        if (self::isBundleLoaded($container, FriendsOfBehatSymfonyExtensionBundle::class)) {
            $container->register(FactoriesContext::class)->addArgument(new Reference('service_container'))->setAutoconfigured(true);
            $container->register(ResetDatabaseContext::class)->setAutowired(true)->setAutoconfigured(true);
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

        if ($config['without_constructor']) {
            $definition->addMethodCall('withoutConstructor');
        }

        if ($config['allow_extra_attributes']) {
            $definition->addMethodCall('allowExtraAttributes');
        }

        if ($config['always_force_properties']) {
            $definition->addMethodCall('alwaysForceProperties');
        }
    }

    private function configureDatabaseResetter(array $config, ContainerBuilder $container): void
    {
        $configurationDefinition = $container->getDefinition('.zenstruck_foundry.configuration');

        if (false === $config['enabled']) {
            $configurationDefinition->addMethodCall('disableDatabaseReset');
        }

        if (isset($config['orm']) && !self::isBundleLoaded($container, DoctrineBundle::class)) {
            throw new \InvalidArgumentException('doctrine/doctrine-bundle should be enabled to use config under "database_resetter.orm".');
        }

        if (isset($config['odm']) && !self::isBundleLoaded($container, DoctrineMongoDBBundle::class)) {
            throw new \InvalidArgumentException('doctrine/mongodb-odm-bundle should be enabled to use config under "database_resetter.odm".');
        }

        $configurationDefinition->setArgument('$ormConnectionsToReset', $config['orm']['connections'] ?? []);
        $configurationDefinition->setArgument('$ormObjectManagersToReset', $config['orm']['object_managers'] ?? []);
        $configurationDefinition->setArgument('$ormResetMode', $config['orm']['reset_mode'] ?? ORMDatabaseResetter::RESET_MODE_SCHEMA);
        $configurationDefinition->setArgument('$odmObjectManagersToReset', $config['odm']['object_managers'] ?? []);
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
