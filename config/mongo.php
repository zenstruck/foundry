<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zenstruck\Foundry\Mongo\MongoPersistenceStrategy;
use Zenstruck\Foundry\Mongo\MongoResetter;
use Zenstruck\Foundry\Mongo\MongoSchemaResetter;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.persistence_strategy.mongo', MongoPersistenceStrategy::class)
            ->args([
                service('doctrine_mongodb'),
            ])
            ->tag('.foundry.persistence_strategy')

        ->set(MongoResetter::class, MongoSchemaResetter::class)
            ->args([
                abstract_arg('managers'),
            ])
            ->tag('.foundry.persistence.schema_resetter')
    ;
};
