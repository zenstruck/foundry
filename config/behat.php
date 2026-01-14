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

use Zenstruck\Foundry\Persistence\Event\AfterPersist;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;
use Zenstruck\Foundry\Test\Behat\FoundryContext;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.behat.factory_resolver', FactoryShortNameResolver::class)
        ->args([
            tagged_iterator('foundry.factory'),
        ])

        ->set('.zenstruck_foundry.behat.object_registry', ObjectRegistry::class)
        ->args([
            service('.zenstruck_foundry.behat.factory_resolver'),
            service('.zenstruck_foundry.persistence_manager'),
        ])
        ->tag('foundry.hook', ['class' => null, 'method' => 'storeLastId', 'event' => AfterPersist::class])
        ->public()

        ->set(FoundryContext::class)
        ->autoconfigure()
        ->args([
            service('.zenstruck_foundry.behat.factory_resolver'),
            service('.zenstruck_foundry.behat.object_registry'),
        ]);
};
