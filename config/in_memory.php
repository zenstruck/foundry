<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zenstruck\Foundry\InMemory\InMemoryFactoryRegistry;
use Zenstruck\Foundry\InMemory\InMemoryRepositoryRegistry;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.in_memory.factory_registry', InMemoryFactoryRegistry::class)
        ->decorate('.zenstruck_foundry.factory_registry')
        ->arg('$decorated', service('.inner'));

    $container->services()
        ->set('.zenstruck_foundry.in_memory.repository_registry', InMemoryRepositoryRegistry::class)
        ->arg('$inMemoryRepositories', abstract_arg('inMemoryRepositories'))
    ;
};
