<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zenstruck\Foundry\InMemory\InMemoryPersistenceStrategy;
use Zenstruck\Foundry\InMemory\InMemoryRepositoryRegistry;

return static function(ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.in_memory.persistence_strategy', InMemoryPersistenceStrategy::class)
        ->arg('$registry', service('.zenstruck_foundry.in_memory.repository_registry'))
        ->arg('$decorated', tagged_iterator('foundry.persistence_strategy'))
        // shadows the Doctrine strategies while the in-memory mode is on
        ->tag('foundry.persistence_strategy', ['priority' => 100]);

    $container->services()
        ->set('.zenstruck_foundry.in_memory.repository_registry', InMemoryRepositoryRegistry::class)
        ->arg('$inMemoryRepositories', abstract_arg('inMemoryRepositories'))
    ;
};
