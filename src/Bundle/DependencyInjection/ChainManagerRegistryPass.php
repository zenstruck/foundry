<?php


namespace Zenstruck\Foundry\Bundle\DependencyInjection;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\ChainManagerRegistry;

class ChainManagerRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $managerRegistries = [];

        if ($container->hasDefinition('doctrine')) {
            $managerRegistries[] = new Reference('doctrine');
        }

        if ($container->hasDefinition('doctrine_mongodb')) {
            $managerRegistries[] = new Reference('doctrine_mongodb');
        }

        if (count($managerRegistries) === 0) {
            throw new \LogicException('Neither doctrine/orm nor doctrine/mongo-odm are present.');
        }

        $container->getDefinition(ChainManagerRegistry::class)
            ->setArgument('$managerRegistries', $managerRegistries);
    }
}
