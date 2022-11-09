<?php

namespace Zenstruck\Foundry\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\Configuration as FoundryConfiguration;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Test\GlobalStateRegistry;

final class GlobalStatePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(FoundryConfiguration::class)) {
            return;
        }

        $globalStateRegistryDefinition = $container->getDefinition(GlobalStateRegistry::class);

        foreach ($this->getBundleConfiguration($container)['global_state'] as $globalStateItem) {
            if ($this->isStoryAsService($container, $globalStateItem)) {
                $globalStateRegistryDefinition->addMethodCall('addStoryAsService', [new Reference($globalStateItem)]);

                continue;
            }

            if ($this->isInvokableService($container, $globalStateItem)) {
                $globalStateRegistryDefinition->addMethodCall('addInvokableService', [new Reference($globalStateItem)]);

                continue;
            }

            if ($this->isStandaloneStory($container, $globalStateItem)) {
                $globalStateRegistryDefinition->addMethodCall('addStandaloneStory', [$globalStateItem]);

                continue;
            }

            throw new \InvalidArgumentException("Given global state \"{$globalStateItem}\" is invalid. Allowed values are invokable services, stories as service or regular stories.");
        }
    }

    private function isStoryAsService(ContainerBuilder $container, string $globalStateItem): bool
    {
        if (!$container->has($globalStateItem)) {
            return false;
        }

        $globalStateItemDefinition = $container->getDefinition($globalStateItem);

        return \count($globalStateItemDefinition->getTag('foundry.story')) > 0;
    }

    private function isInvokableService(ContainerBuilder $container, string $globalStateItem): bool
    {
        if (!$container->has($globalStateItem)) {
            return false;
        }

        $globalStateItemDefinition = $container->getDefinition($globalStateItem);

        return (new \ReflectionClass($globalStateItemDefinition->getClass()))->hasMethod('__invoke');
    }

    private function isStandaloneStory(ContainerBuilder $container, string $globalStateItem): bool
    {
        if ($container->has($globalStateItem)) {
            return false;
        }

        return \class_exists($globalStateItem) && \is_a($globalStateItem, Story::class, true);
    }

    private function getBundleConfiguration(ContainerBuilder $container): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $container->getExtensionConfig('zenstruck_foundry'));
    }
}
