<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zenstruck\Foundry\ORM\DoctrineOrmVersionGuesser;
use Zenstruck\Foundry\ORM\OrmV2PersistenceStrategy;
use Zenstruck\Foundry\ORM\OrmV3PersistenceStrategy;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.persistence_strategy.orm', DoctrineOrmVersionGuesser::isOrmV3() ? OrmV3PersistenceStrategy::class : OrmV2PersistenceStrategy::class)
            ->args([
                service('doctrine'),
                abstract_arg('config'),
            ])
            ->tag('.foundry.persistence_strategy')
    ;
};
