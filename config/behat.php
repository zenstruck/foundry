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

use Zenstruck\Foundry\InMemory\InMemoryRepositoryRegistry;
use Zenstruck\Foundry\Test\Behat\FactoryResolver;
use Zenstruck\Foundry\Test\Behat\FoundryContext;

return static function(ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.behat.factory_resolver', FactoryResolver::class)
        ->args([
            tagged_iterator('foundry.factory'),
        ])

        ->set(FoundryContext::class)
        ->autoconfigure()
        ->args([
            service('.zenstruck_foundry.behat.factory_resolver'),
        ])
    ;
};
