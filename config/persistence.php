<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zenstruck\Foundry\Persistence\PersistenceManager;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.persistence_manager', PersistenceManager::class)
            ->args([
                tagged_iterator('.foundry.persistence_strategy'),
                tagged_iterator('.foundry.persistence.database_resetter'),
                tagged_iterator('.foundry.persistence.schema_resetter'),
            ])
    ;
};
