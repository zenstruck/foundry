<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\Test\Behat\Listener\BootConfigurationListener;
use Zenstruck\Foundry\Test\Behat\Listener\LoadFixturesListener;

final class FoundryExtension implements Extension
{
    public function process(ContainerBuilder $container): void
    {
    }

    public function getConfigKey(): string
    {
        return 'zenstruck_foundry';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $container->register('.zenstruck_foundry.behat.tag_parser', BehatTagParser::class);

        $container->register('.zenstruck_foundry.behat.listener.boot_configuration', BootConfigurationListener::class)
            ->setArgument('$symfonyKernel', new Reference('fob_symfony.kernel'))
            ->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);

        $container->register('.zenstruck_foundry.behat.listener.load_fixture', LoadFixturesListener::class)
            ->setArgument('$symfonyKernel', new Reference('fob_symfony.kernel'))
            ->setArgument('$tagParser', new Reference('.zenstruck_foundry.behat.tag_parser'))
            ->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);
    }
}
