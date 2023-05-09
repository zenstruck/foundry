<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zenstruck\Foundry\Bundle\DependencyInjection\ChainManagerRegistryPass;
use Zenstruck\Foundry\Bundle\DependencyInjection\GlobalStatePass;
use Zenstruck\Foundry\Bundle\DependencyInjection\RegisterFakerProvidersPass;
use Zenstruck\Foundry\Bundle\DependencyInjection\ZenstruckFoundryExtension;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Must be at src root to be autoconfigured by Symfony Flex.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFoundryBundle extends Bundle
{
    public function boot(): void
    {
        if (!BaseFactory::isBooted()) {
            BaseFactory::boot(
                $this->container->get('.zenstruck_foundry.factory_manager'),
                $this->container->get('.zenstruck_foundry.configuration')
            );
        }

        if (\class_exists(PersistentObjectFactory::class) && !PersistentObjectFactory::isPersistentObjectFactoryBooted()) {
            PersistentObjectFactory::bootPersistentObjectFactory($this->container->get('.zenstruck_foundry.persistence_manager'));
        }
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ChainManagerRegistryPass());
        $container->addCompilerPass(new GlobalStatePass());
        $container->addCompilerPass(new RegisterFakerProvidersPass());
    }

    protected function createContainerExtension(): ?ExtensionInterface
    {
        return new ZenstruckFoundryExtension();
    }
}
