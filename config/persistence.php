<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\Proxy\KernelTerminateListener;
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
    ;

    if (PHP_VERSION_ID >= 80400) {
        $container->services()->set('.foundry.proxy.kernel_terminate_listener', KernelTerminateListener::class)
            ->tag('kernel.event_listener', ['event' => TerminateEvent::class, 'method' => '__invoke'])
        ;
    }
};
