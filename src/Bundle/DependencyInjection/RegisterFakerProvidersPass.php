<?php

namespace Zenstruck\Foundry\Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class RegisterFakerProvidersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('foundry.faker_provider') as $id => $tags) {
            $container
                ->getDefinition('.zenstruck_foundry.faker')
                ->addMethodCall('addProvider', array(new Reference($id)))
            ;
        }
    }
}
