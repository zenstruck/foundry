<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Zenstruck\Foundry\ORM\DoctrineOrmVersionGuesser;
use Zenstruck\Foundry\ORM\OrmV2PersistenceStrategy;
use Zenstruck\Foundry\ORM\OrmV3PersistenceStrategy;
use Zenstruck\Foundry\ORM\ResetDatabase\BaseOrmResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\DamaDatabaseResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmResetter;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.persistence_strategy.orm', DoctrineOrmVersionGuesser::isOrmV3() ? OrmV3PersistenceStrategy::class : OrmV2PersistenceStrategy::class)
            ->args([
                service('doctrine'),
            ])
            ->tag('.foundry.persistence_strategy')

        ->set('.zenstruck_foundry.persistence.database_resetter.orm.abstract', BaseOrmResetter::class)
            ->arg('$registry', service('doctrine'))
            ->arg('$managers', service('managers'))
            ->arg('$connections', service('connections'))
            ->abstract()

        ->set(OrmResetter::class, /* class to be defined thanks to the configuration */)
            ->parent('.zenstruck_foundry.persistence.database_resetter.orm.abstract')
            ->tag('.foundry.persistence.database_resetter')
            ->tag('.foundry.persistence.schema_resetter')
    ;

    if (\class_exists(StaticDriver::class)) {
        $container->services()
            ->set('.zenstruck_foundry.persistence.database_resetter.orm.dama', DamaDatabaseResetter::class)
                ->decorate(OrmResetter::class, priority: 10)
                ->args([
                    service('.inner'),
                ])
        ;
    }
};
