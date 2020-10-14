<?php

namespace Zenstruck\Foundry\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Story;

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

        $container->registerForAutoconfiguration(ModelFactory::class)
            ->addTag('foundry.factory')
        ;

        $this->configureFaker($mergedConfig['faker'], $container);
        $this->configureDefaultInstantiator($mergedConfig['instantiator'], $container);

        if ($mergedConfig['auto_refresh_proxies']) {
            $container->getDefinition(Configuration::class)
                ->addMethodCall('alwaysAutoRefreshProxies')
            ;
        }
    }

    private function configureFaker(array $config, ContainerBuilder $container): void
    {
        if ($config['service']) {
            $container->setAlias('zenstruck_foundry.faker', $config['service']);

            return;
        }

        if ($config['locale']) {
            $container->getDefinition('zenstruck_foundry.faker')->addArgument($config['locale']);
        }
    }

    private function configureDefaultInstantiator(array $config, ContainerBuilder $container): void
    {
        if ($config['service']) {
            $container->setAlias('zenstruck_foundry.default_instantiator', $config['service']);

            return;
        }

        $definition = $container->getDefinition('zenstruck_foundry.default_instantiator');

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
}
