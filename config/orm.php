<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Zenstruck\Foundry\ORM\DoctrineOrmVersionGuesser;
use Zenstruck\Foundry\ORM\OrmV2PersistenceStrategy;
use Zenstruck\Foundry\ORM\OrmV3PersistenceStrategy;
use Zenstruck\Foundry\ORM\ResetDatabase\DamaDatabaseResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\DamaSchemaResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmDatabaseResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmSchemaResetter;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.persistence_strategy.orm', DoctrineOrmVersionGuesser::isOrmV3() ? OrmV3PersistenceStrategy::class : OrmV2PersistenceStrategy::class)
            ->args([
                service('doctrine'),
                abstract_arg('config'),
            ])
            ->tag('.foundry.persistence_strategy')
        ->set('.zenstruck_foundry.persistence.database_resetter.orm', OrmDatabaseResetter::class)
            ->args([
                service('doctrine'),
                abstract_arg('managers'),
                abstract_arg('connections'),
            ])
            ->tag('.foundry.persistence.database_resetter')
        ->set('.zenstruck_foundry.persistence.schema_resetter.orm', OrmSchemaResetter::class)
            ->args([
                service('doctrine'),
                abstract_arg('managers'),
                abstract_arg('connections'),
            ])
            ->tag('.foundry.persistence.schema_resetter')
    ;

    if (\class_exists(StaticDriver::class)) {
        $container->services()
            ->set('.zenstruck_foundry.persistence.database_resetter.orm.dama', DamaDatabaseResetter::class)
                ->decorate('.zenstruck_foundry.persistence.database_resetter.orm')
                ->args([
                    service('.inner'),
                ])
                ->tag('.foundry.persistence.database_resetter')
            ->set('.zenstruck_foundry.persistence.schema_resetter.orm.dama', DamaSchemaResetter::class)
                ->decorate('.zenstruck_foundry.persistence.schema_resetter.orm')
                ->args([
                    service('.inner'),
                ])
                ->tag('.foundry.persistence.schema_resetter')
        ;
    }
};
