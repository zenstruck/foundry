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

use Zenstruck\Foundry\Mongo\MongoPersistenceStrategy;
use Zenstruck\Foundry\Mongo\MongoResetter;
use Zenstruck\Foundry\Mongo\MongoSchemaResetter;

return static function(ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.persistence_strategy.mongo', MongoPersistenceStrategy::class)
            ->args([
                service('doctrine_mongodb'),
            ])
            ->tag('foundry.persistence_strategy')

        ->set(MongoResetter::class, MongoSchemaResetter::class)
            ->args([
                abstract_arg('managers'),
            ])
            ->tag('.foundry.persistence.schema_resetter')
    ;
};
