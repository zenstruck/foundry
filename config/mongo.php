<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zenstruck\Foundry\Mongo\MongoPersistenceStrategy;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.persistence_strategy.mongo', MongoPersistenceStrategy::class)
            ->args([
                service('doctrine_mongodb'),
                abstract_arg('config'),
            ])
            ->tag('.foundry.persistence_strategy')
    ;
};
