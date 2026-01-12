<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
        $container->register('.zenstruck_foundry.behat.listener', BehatListener::class)
            ->setArgument('$symfonyKernel', new Reference('fob_symfony.kernel'))
            ->addTag(EventDispatcherExtension::SUBSCRIBER_TAG)
        ;
    }
}
