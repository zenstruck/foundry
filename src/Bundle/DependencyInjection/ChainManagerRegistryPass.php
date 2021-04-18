<?php

namespace Zenstruck\Foundry\Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\ChainManagerRegistry;

final class ChainManagerRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ChainManagerRegistry::class)) {
            return;
        }

        $managerRegistries = [];

        if ($container->hasDefinition('doctrine')) {
            $managerRegistries[] = new Reference('doctrine');
        }

        if ($container->hasDefinition('doctrine_mongodb')) {
            $managerRegistries[] = new Reference('doctrine_mongodb');
        }

        if (0 === \count($managerRegistries)) {
            throw new \LogicException('Neither doctrine/orm nor mongodb-odm are present.');
        }

        $container->getDefinition(ChainManagerRegistry::class)
            ->setArgument('$managerRegistries', $managerRegistries)
        ;
    }
}
