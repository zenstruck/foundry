<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zenstruck\Foundry\Command\LoadFixturesCommand;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.persistence_manager', PersistenceManager::class)
            ->args([
                tagged_iterator('.foundry.persistence_strategy'),
                service('.zenstruck_foundry.persistence.reset_database_manager'),
            ])
        ->set('.zenstruck_foundry.persistence.reset_database_manager', ResetDatabaseManager::class)
            ->args([
                tagged_iterator('.foundry.persistence.database_resetter'),
                tagged_iterator('.foundry.persistence.schema_resetter'),
            ])

        ->set('.zenstruck_foundry.command.load_fixtures', LoadFixturesCommand::class)
            ->arg('$databaseResetters', tagged_iterator('.foundry.persistence.database_resetter'))
            ->arg('$kernel', service('kernel'))
            ->tag('console.command', [
                'command' => 'foundry:load-fixtures|foundry:load-stories|foundry:load-story',
                'description' => 'Load stories which are marked with #[AsFixture] attribute.',
            ])
    ;
};
