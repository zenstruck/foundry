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

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\Story;

final class GlobalStatePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('.zenstruck_foundry.configuration')) {
            return;
        }

        $globalStateRegistryDefinition = $container->getDefinition('.zenstruck_foundry.global_state_registry');

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

        return [] !== $globalStateItemDefinition->getTag('foundry.story');
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

    /**
     * @return mixed[]
     */
    private function getBundleConfiguration(ContainerBuilder $container): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $container->getExtensionConfig('zenstruck_foundry'));
    }
}
