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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ChainManagerRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('.zenstruck_foundry.chain_manager_registry')) {
            return;
        }

        $managerRegistries = [];

        if ($container->hasDefinition('doctrine')) {
            $managerRegistries[] = new Reference('doctrine');
        }

        if ($container->hasDefinition('doctrine_mongodb')) {
            $managerRegistries[] = new Reference('doctrine_mongodb');
        }

        $container->getDefinition('.zenstruck_foundry.chain_manager_registry')
            ->setArgument('$managerRegistries', $managerRegistries)
        ;
    }
}
